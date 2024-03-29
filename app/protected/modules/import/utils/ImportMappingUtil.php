<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2013 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU Affero General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
     * details.
     *
     * You should have received a copy of the GNU Affero General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 27 North Wacker Drive
     * Suite 370 Chicago, IL 60606. or at email address contact@zurmo.com.
     *
     * The interactive user interfaces in original and modified versions
     * of this program must display Appropriate Legal Notices, as required under
     * Section 5 of the GNU Affero General Public License version 3.
     *
     * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
     * these Appropriate Legal Notices must retain the display of the Zurmo
     * logo and Zurmo copyright notice. If the display of the logo is not reasonably
     * feasible for technical reasons, the Appropriate Legal Notices must display the words
     * "Copyright Zurmo Inc. 2013. All rights reserved".
     ********************************************************************************/

    /**
     * Helper utility for working with import mapping.
     */
    class ImportMappingUtil
    {
        /**
         * Given an import data's table name, create a basic mapping data array that has the correct starting
         * elements set as null.  This will ensure the mapping data array is always structured correctly.  Each key
         * will be a column name from the table.  Throws an exception if the table is missing rows.
         * @param string $tableName
         * @return array $mappingData
         */
        public static function makeMappingDataByTableName($tableName)
        {
            assert('is_string($tableName)');
            $firstRowData = ZurmoRedBean::$writer->getFirstRowByTableName($tableName);

            if (count($firstRowData) == 1 || count($firstRowData) == 0)
            {
                throw new NoRowsInTableException();
            }
            //Handle scenario where every column is null. Similiar to scenario below it with no data in file but
            //making a row anyways.
            $allValuesAreNull = true;
            foreach ($firstRowData as $columnName => $value)
            {
                if ($value != null && $columnName != 'id')
                {
                    $allValuesAreNull = false;
                    break;
                }
            }
            if ($allValuesAreNull)
            {
                throw new NoRowsInTableException();
            }
            //handles scenario where there is no data in the file, but because there are a few bytes,
            //it creates a single row.
            if (count($firstRowData) == 2)
            {
                foreach ($firstRowData as $columnName => $value)
                {
                    if (!in_array($columnName, ImportDatabaseUtil::getReservedColumnNames()) && $value == null)
                    {
                        if (ImportDatabaseUtil::getCount($tableName) == 1)
                        {
                            throw new NoRowsInTableException();
                        }
                    }
                }
            }
            $mappingData = array();
            foreach ($firstRowData as $columnName => $notUsed)
            {
                if (!in_array($columnName, ImportDatabaseUtil::getReservedColumnNames()))
                {
                    $mappingData[$columnName] = array('type'                        => 'importColumn',
                                                      'attributeIndexOrDerivedType' => null,
                                                      'mappingRulesData'            => null);
                }
            }
            return $mappingData;
        }

        /**
         * Given an array of mapping data, extract the 'attributeIndexOrDerivedType' from each sub array
         * in the mapping data and return an array of the attributeIndexOrDerivedType.  This is useful if you
         * just need a single dimension array of this information based on the mapping data.
         * @param array $mappingData
         * @return array
         */
        public static function getMappedAttributeIndicesOrDerivedAttributeTypesByMappingData($mappingData)
        {
            assert('is_array($mappingData)');
            $mappedAttributeIndicesOrDerivedAttributeTypes = null;
            foreach ($mappingData as $data)
            {
                if ($data['attributeIndexOrDerivedType'] != null)
                {
                    $mappedAttributeIndicesOrDerivedAttributeTypes[] = $data['attributeIndexOrDerivedType'];
                }
            }
            return $mappedAttributeIndicesOrDerivedAttributeTypes;
        }

        /**
         * Given a column count, create a suitable column name. An example would be column_5, where 5 would have been
         * the column count that was passed in.  This pattern column_x matches the pattern used by redbean to generate
         * column names.
         * @param integer $columnCount
         */
        public static function makeExtraColumnNameByColumnCount($columnCount)
        {
            assert('is_int($columnCount)');
            return 'column_' . ($columnCount + 1);
        }

        /**
         * Given an array of post data, re-index the column names that are of type 'extraColumn'. This method is
         * needed since it is possible that a user can add, remove extra columns in such a way that produces column
         * names that are missing index orders.  This will fix the extra column column names and return the data.
         * @param array $postData
         */
        public static function reIndexExtraColumnNamesByPostData($postData)
        {
            assert('is_array($postData)');
            $reIndexedData     = array();
            $importColumnCount = 0;
            $tempData          = array();
            foreach ($postData as $columnName => $data)
            {
                assert('$data["type"] == "importColumn" || $data["type"] == "extraColumn"');
                if ($data['type'] == 'extraColumn')
                {
                    $tempData[] = $data;
                }
                else
                {
                    $reIndexedData[$columnName] = $data;
                    $importColumnCount++;
                }
            }
            $extraColumnStartingCount = $importColumnCount - 1;
            foreach ($tempData as $data)
            {
                $reIndexedData[self::makeExtraColumnNameByColumnCount($extraColumnStartingCount)] = $data;
                $extraColumnStartingCount++;
            }
            return $reIndexedData;
        }

        /**
         *
         * Make an array of index/values that are the column names and their respective labels.
         * @param array $mappingData
         * @param array $importRulesType
         */
        public static function makeColumnNamesAndAttributeIndexOrDerivedTypeLabels($mappingData, $importRulesType)
        {
            assert('is_array($mappingData)');
            assert('is_string($importRulesType)');
            $columnNamesAndAttributeIndexOrDerivedTypeLabels = array();
            foreach ($mappingData as $columnName => $columnData)
            {
                if ($columnData['attributeIndexOrDerivedType'] != null)
                {
                    $attributeImportRules = AttributeImportRulesFactory::
                                            makeByImportRulesTypeAndAttributeIndexOrDerivedType(
                                            $importRulesType, $columnData['attributeIndexOrDerivedType']);
                    $columnNamesAndAttributeIndexOrDerivedTypeLabels[$columnName] = $attributeImportRules->getDisplayLabel();
                }
                else
                {
                    $columnNamesAndAttributeIndexOrDerivedTypeLabels[$columnName] = null;
                }
            }
            return $columnNamesAndAttributeIndexOrDerivedTypeLabels;
        }

        public static function makeBooleanColumnMappingData($attributeName)
        {
            return array('attributeIndexOrDerivedType' => $attributeName,
                         'type'                        => 'importColumn');
        }

        /**
         * @param string $attributeName
         * @param Currency $currency
         * @param null $defaultValue
         * @param int $rateToBase
         * @return array
         */
        public static function makeCurrencyColumnMappingData($attributeName, $currency, $defaultValue = null, $rateToBase = 1)
        {
            return array('attributeIndexOrDerivedType' => $attributeName,
                         'type'                        => 'importColumn',
                         'mappingRulesData'            => array(
                             'DefaultValueModelAttributeMappingRuleForm' =>
                             array('defaultValue' => $defaultValue),
                             'CurrencyRateToBaseModelAttributeMappingRuleForm' =>
                                 array('rateToBase' => $rateToBase),
                             'CurrencyIdModelAttributeMappingRuleForm' =>
                                 array('id' => $currency->id)));
        }

        /**
         * @param string $attributeName
         * @param null $defaultValue
         * @param string $format
         * @return array
         */
        public static function makeDateColumnMappingData($attributeName, $defaultValue = null,
                                                             $format = 'MM-dd-yyyy')
        {
            return array('attributeIndexOrDerivedType'               => $attributeName,
                         'type'                                      => 'importColumn',
                         'mappingRulesData'                          => array(
                         'DefaultValueModelAttributeMappingRuleForm' => array('defaultValue' => $defaultValue),
                          'ValueFormatMappingRuleForm'               => array('format' => $format)));
        }

        /**
         * @param string $attributeName
         * @param null $defaultValue
         * @param string $format
         * @return array
         */
        public static function makeDateTimeColumnMappingData($attributeName, $defaultValue = null,
                                                             $format = 'MM-dd-yyyy hh:mm')
        {
            return array('attributeIndexOrDerivedType'               => $attributeName,
                         'type'                                      => 'importColumn',
                         'mappingRulesData'                          => array(
                         'DefaultValueModelAttributeMappingRuleForm' => array('defaultValue' => $defaultValue),
                          'ValueFormatMappingRuleForm'               => array('format' => $format)));
        }

        /**
         * @param string $attributeName
         * @param null $defaultValue
         * @param null $importInstructionsData
         * @return array
         */
        public static function makeDropDownColumnMappingData($attributeName, $defaultValue = null,
                                                             $customFieldsInstructionData = null)
        {
            if ($customFieldsInstructionData == null)
            {
                $customFieldsInstructionData = array(CustomFieldsInstructionData::ADD_MISSING_VALUES => array());
            }
            return array('attributeIndexOrDerivedType'  => $attributeName,
                         'type'                         => 'importColumn',
                         'mappingRulesData'             => array('DefaultValueDropDownModelAttributeMappingRuleForm' =>
                                                           array('defaultValue' => $defaultValue)),
                         'customFieldsInstructionData' => $customFieldsInstructionData);
        }

        /**
         * @param string $attributeName
         * @param null $defaultValue
         * @param null $importInstructionsData
         * @return array
         */
        public static function makeMultiSelectDropDownColumnMappingData($attributeName, $defaultValue = null,
                                                                        $customFieldsInstructionData = null)
        {
            if ($customFieldsInstructionData == null)
            {
                $customFieldsInstructionData = array(CustomFieldsInstructionData::ADD_MISSING_VALUES => array());
            }
            return array('attributeIndexOrDerivedType'  => $attributeName,
                         'type'                         => 'importColumn',
                         'mappingRulesData'             => array('DefaultValueMultiSelectDropDownModelAttributeMappingRuleForm' =>
                                                           array('defaultValue' => $defaultValue)),
                         'customFieldsInstructionData' => $customFieldsInstructionData);
        }

        /**
         * @param string $attributeName
         * @param null $defaultValue
         * @param null $importInstructionsData
         * @return array
         */
        public static function makeTagCloudColumnMappingData($attributeName, $defaultValue = null,
                                                             $customFieldsInstructionData = null)
        {
            if ($customFieldsInstructionData == null)
            {
                $customFieldsInstructionData = array(CustomFieldsInstructionData::ADD_MISSING_VALUES => array());
            }
            return array('attributeIndexOrDerivedType'  => $attributeName,
                         'type'                         => 'importColumn',
                         'mappingRulesData'             => array('DefaultValueMultiSelectDropDownModelAttributeMappingRuleForm' =>
                                                           array('defaultValue' => $defaultValue)),
                         'customFieldsInstructionData' => $customFieldsInstructionData);
        }

        /**
         * @param string $attributeName
         * @param null $defaultValue
         * @return array
         */
        public static function makeEmailColumnMappingData($attributeName, $defaultValue = null)
        {
            return array('attributeIndexOrDerivedType' => $attributeName,
                         'type'                        => 'importColumn',
                         'mappingRulesData'            => array(
                             'DefaultValueModelAttributeMappingRuleForm' =>
                             array('defaultValue' => $defaultValue)));
        }

        /**
         * @param string $attributeName
         * @param null $defaultValue
         * @return array
         */
        public static function makeFloatColumnMappingData($attributeName, $defaultValue = null)
        {
            return array('attributeIndexOrDerivedType' => $attributeName,
                         'type'                        => 'importColumn',
                         'mappingRulesData'            => array(
                             'DefaultValueModelAttributeMappingRuleForm' =>
                             array('defaultValue' => $defaultValue)));
        }

        public static function makeIntegerColumnMappingData($attributeName, $defaultValue = null)
        {
            return array('attributeIndexOrDerivedType' => $attributeName,
                         'type'                        => 'importColumn',
                         'mappingRulesData'            => array(
                             'DefaultValueModelAttributeMappingRuleForm' =>
                             array('defaultValue' => $defaultValue)));
        }

        /**
         * @param string $attributeName
         * @param $type
         * @return array
         */
        public static function makeHasOneColumnMappingData($attributeName,
                                                           $type = RelatedModelValueTypeMappingRuleForm::ZURMO_MODEL_ID)
        {
            return array('attributeIndexOrDerivedType'          => $attributeName,
                         'type'                                 => 'importColumn',
                         'mappingRulesData'                     => array(
                         'RelatedModelValueTypeMappingRuleForm' => array('type' => $type)));
        }

        /**
         * @param string $derivedAttributeName
         * @param $type
         * @return array
         */
        public static function makeModelDerivedColumnMappingData($derivedAttributeName,
                                                                 $type = IdValueTypeMappingRuleForm::EXTERNAL_SYSTEM_ID)
        {
            return array('attributeIndexOrDerivedType'                        => $derivedAttributeName,
                         'type'                                               => 'importColumn',
                         'mappingRulesData'                                   => array(
                         'IdValueTypeMappingRuleForm'                         => array('type' => $type),
                         'DefaultModelNameIdDerivedAttributeMappingRuleForm'  => array('defaultModelId' => null)));
        }

        /**
         * @param string $attributeName
         * @param null $defaultValue
         * @return array
         */
        public static function makeStringColumnMappingData($attributeName, $defaultValue = null)
        {
            return array('attributeIndexOrDerivedType' => $attributeName,
                         'type'                        => 'importColumn',
                         'mappingRulesData'            => array(
                             'DefaultValueModelAttributeMappingRuleForm' =>
                             array('defaultValue' => $defaultValue)));
        }

        /**
         * @param string $attributeName
         * @return array
         */
        public static function makeTextAreaColumnMappingData($attributeName)
        {
            return array('attributeIndexOrDerivedType' => $attributeName,
                         'type'                        => 'importColumn');
        }

        /**
         * @param string $attributeName
         * @param null $defaultValue
         * @return array
         */
        public static function makeUrlColumnMappingData($attributeName, $defaultValue = null)
        {
            return array('attributeIndexOrDerivedType' => $attributeName,
                         'type'                        => 'importColumn',
                         'mappingRulesData'            => array(
                             'DefaultValueModelAttributeMappingRuleForm' =>
                             array('defaultValue' => $defaultValue)));
        }
    }
?>