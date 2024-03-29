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
     * Utility helper class for rendering the content for the @see ImportWizardMappingView and
     * @see ImportWizardMappingExtraColumnView. This class helps to separate the logic for rendering content
     * for the inputs on these views, so they can be reused more easily.
     */
    class MappingFormLayoutUtil
    {
        /**
         * The name of the class for the form used. Typically it would be ImportWizardForm.
         * @var string
         */
        protected $mappingFormModelClassName;

        protected $form;

        protected $mappableAttributeIndicesAndDerivedTypesForImportColumns;

        protected $mappableAttributeIndicesAndDerivedTypesForExtraColumns;

        /**
         * @param string $mappingFormModelClassName
         * @param ZurmoActiveForm $form
         * @param array $mappableAttributeIndicesAndDerivedTypesForImportColumns
         * @param array $mappableAttributeIndicesAndDerivedTypesForExtraColumns
         */
        public function __construct($mappingFormModelClassName, $form,
                                    $mappableAttributeIndicesAndDerivedTypesForImportColumns,
                                    $mappableAttributeIndicesAndDerivedTypesForExtraColumns)
        {
            assert('is_string($mappingFormModelClassName)');
            assert('$form instanceof ZurmoActiveForm');
            assert('is_array($mappableAttributeIndicesAndDerivedTypesForImportColumns)');
            assert('is_array($mappableAttributeIndicesAndDerivedTypesForExtraColumns)');
            $this->mappingFormModelClassName                               = $mappingFormModelClassName;
            $this->form                                                    = $form;
            $this->form->id                                                = 'edit-form';
            $this->mappableAttributeIndicesAndDerivedTypesForImportColumns = $mappableAttributeIndicesAndDerivedTypesForImportColumns;
            $this->mappableAttributeIndicesAndDerivedTypesForExtraColumns  = $mappableAttributeIndicesAndDerivedTypesForExtraColumns;
        }

        /**
         * Used for testing purposes.
         */
        public function getMappableAttributeIndicesAndDerivedTypesForImportColumns()
        {
            return $this->mappableAttributeIndicesAndDerivedTypesForImportColumns;
        }

        /**
         * Used for testing purposes.
         */
        public function getMappableAttributeIndicesAndDerivedTypesForExtraColumns()
        {
            return $this->mappableAttributeIndicesAndDerivedTypesForExtraColumns;
        }

        /**
         * @param string $columnName
         * @param string $columnType
         * @param string $attributeIndexOrDerivedType
         * @param string $ajaxOnChangeUrl
         * @return string
         */
        public function renderAttributeAndColumnTypeContent  ($columnName,
                                                              $columnType,
                                                              $attributeIndexOrDerivedType,
                                                              $ajaxOnChangeUrl)
        {
            $content  = $this->renderAttributeDropDownContent($columnName,
                                                              $columnType,
                                                              $attributeIndexOrDerivedType,
                                                              $ajaxOnChangeUrl);
            $content .= $this->renderColumnTypeContent       ($columnName,
                                                              $columnType,
                                                              $attributeIndexOrDerivedType);
            return $content;
        }

        protected function renderAttributeDropDownContent($columnName,
                                                          $columnType,
                                                          $attributeIndexOrDerivedType,
                                                          $ajaxOnChangeUrl)
        {
            assert('is_string($columnName)');
            assert('$columnType == "importColumn" || $columnType == "extraColumn"');
            assert('is_string($attributeIndexOrDerivedType) || $attributeIndexOrDerivedType == null');
            assert('is_string($ajaxOnChangeUrl)');
            $name        = $this->mappingFormModelClassName . '[' . $columnName . '][attributeIndexOrDerivedType]';
            $id          = $this->mappingFormModelClassName . '_' . $columnName . '_attributeIndexOrDerivedType';
            $htmlOptions = array('id' => $id,
                'empty' => Zurmo::t('ImportModule', 'Do not map this field')
            );
            Yii::app()->clientScript->registerScript('AttributeDropDown' . $id,
                                                     $this->renderAttributeDropDownOnChangeScript($id,
                                                     $columnName,
                                                     $columnType,
                                                     $ajaxOnChangeUrl));

                                                     if ($columnType == 'importColumn')
            {
                $mappableAttributeIndicesAndDerivedTypes = $this->mappableAttributeIndicesAndDerivedTypesForImportColumns;
            }
            else
            {
                $mappableAttributeIndicesAndDerivedTypes = $this->mappableAttributeIndicesAndDerivedTypesForExtraColumns;
            }
            $content = ZurmoHtml::dropDownList($name,
                                       $attributeIndexOrDerivedType,
                                       $mappableAttributeIndicesAndDerivedTypes,
                                       $htmlOptions);
            if ($columnType == 'extraColumn')
            {
                $content .= ZurmoHtml::link(Zurmo::t('ImportModule', 'Remove Field'),
                            '#', array('class' => 'remove-extra-column-link z-link'));
                Yii::app()->clientScript->registerScript('mappingExtraColumnRemoveLink', "
                $('.remove-extra-column-link').click( function()
                    {
                        $(this).parent().parent().remove();
                    }
                );");
            }
            return $content;
        }

        protected function renderColumnTypeContent($columnName, $columnType, $attributeIndexOrDerivedType)
        {
            assert('is_string($columnName)');
            assert('$columnType == "importColumn" || $columnType == "extraColumn"');
            $idInputHtmlOptions  = array('id' => $this->mappingFormModelClassName . '_' . $columnName . '_type');
            $hiddenInputName     = $this->mappingFormModelClassName . '[' . $columnName . '][type]';
            return ZurmoHtml::hiddenField($hiddenInputName, $columnType, $idInputHtmlOptions);
        }

        /**
         * @param string $columnName
         * @param string $headerValue
         * @return string
         */
        public function renderHeaderColumnContent($columnName, $headerValue)
        {
            assert('is_string($columnName)');
            assert('is_string($headerValue) || $headerValue == null');
            $content = self::renderChoppedStringContent($headerValue);
            return $content;
        }

        /**
         * @param string $columnName
         * @param string $sampleValue
         * @return string
         */
        public function renderImportColumnContent($columnName, $sampleValue)
        {
            assert('is_string($columnName)');
            assert('is_string($sampleValue) || $sampleValue == null');
            $sampleValueContent = self::renderChoppedStringContent($sampleValue);
            $content = '<div id="' . self::resolveSampleColumnIdByColumnName($columnName) . '" class="column-import-data">' . $sampleValueContent . '</div>';
            return $content;
        }

        /**
         * @param string $columnName
         * @param string $attributeIndexOrDerivedType
         * @param string $importRulesType
         * @param string $columnType
         * @param array $mappingRuleFormsAndElementTypes
         * @return string
         */
        public function renderMappingRulesElements($columnName,
                                                   $attributeIndexOrDerivedType,
                                                   $importRulesType,
                                                   $columnType,
                                                   $mappingRuleFormsAndElementTypes)
        {
            assert('is_string($columnName)');
            assert('is_string($attributeIndexOrDerivedType) || $attributeIndexOrDerivedType == null');
            assert('is_string($importRulesType)');
            assert('$columnType == "importColumn" || $columnType == "extraColumn"');
            assert('is_array($mappingRuleFormsAndElementTypes) || $mappingRuleFormsAndElementTypes == null');
            $content                      = null;
            $multipleMappingRulesCssClass = null;
            if ($attributeIndexOrDerivedType != null)
            {
                if ($mappingRuleFormsAndElementTypes == null)
                {
                    $attributeImportRules            = AttributeImportRulesFactory::
                                                       makeByImportRulesTypeAndAttributeIndexOrDerivedType(
                                                           $importRulesType,
                                                           $attributeIndexOrDerivedType);
                    $mappingRuleFormsAndElementTypes = MappingRuleFormAndElementTypeUtil::
                                                       makeCollectionByAttributeImportRules(
                                                           $attributeImportRules,
                                                           $attributeIndexOrDerivedType,
                                                           $columnType);
                }
                if (count($mappingRuleFormsAndElementTypes) > 0)
                {
                    $title = Zurmo::t('ImportModule', 'Rules');
                }
                else
                {
                    $title = null;
                }
                $content .= ZurmoHtml::tag('h4', array(), $title);
                foreach ($mappingRuleFormsAndElementTypes as $notUsed => $ruleFormAndElementType)
                {
                    $mappingRuleForm        = $ruleFormAndElementType['mappingRuleForm'];
                    $elementClassName       = $ruleFormAndElementType['elementType'] . 'Element';
                    $classToEvaluate        = new ReflectionClass($elementClassName);
                    if ($classToEvaluate->implementsInterface('DerivedElementInterface'))
                    {
                        $attributeName = 'null';
                    }
                    else
                    {
                        $attributeName          = $mappingRuleForm::getAttributeName();
                    }
                    $params                 = array();
                    $params['inputPrefix']  = array($this->mappingFormModelClassName, $columnName, 'mappingRulesData',
                                                    get_class($mappingRuleForm));
                    $element                = new $elementClassName(
                                                  $mappingRuleForm,
                                                  $attributeName,
                                                  $this->form,
                                                  $params);
                    $element->editableTemplate = '<div>{label}{content}{error}</div>';
                    $content .= $element->render();
                }
            }

            $content = ZurmoHtml::tag('div', array('id' => self::getMappingRulesDivIdByColumnName($columnName),
                                                   'class' => 'mapping-rules'), $content);
            return $content;
        }

        /**
         * Given an array of MappingFormLayoutUtil metadata, render the html rows and return this content as a string.
         * @param array $metadata
         * @return string with the rendered content.
         */
        public static function renderMappingDataMetadataWithRenderedElements($metadata)
        {
            assert('is_array($metadata)');
            $content = null;
            foreach ($metadata['rows'] as $row)
            {
                $content .= '<tr>';
                assert('count($row["cells"]) > 0');
                foreach ($row['cells'] as $cellContent)
                {
                    $content .= '<td>';
                    $content .= $cellContent;
                    $content .= '</td>';
                }
                $content .= '</tr>';
            }
            return $content;
        }

        protected static function getMappingRulesDivIdByColumnName($columnName)
        {
            return $columnName . '-mapping-rules';
        }

        protected function renderAttributeDropDownOnChangeScript($id, $columnName, $columnType, $ajaxOnChangeUrl)
        {
            assert('is_string($id)');
            assert('is_string($columnName)');
            $mappingRulesDivId = self::getMappingRulesDivIdByColumnName($columnName);
            $ajaxSubmitScript  = ZurmoHtml::ajax(array(
                    'type'    => 'GET',
                    'data'    => 'js:\'columnName=' . $columnName . '&columnType=' . $columnType .
                                 '&attributeIndexOrDerivedType=\' + $(this).val()',
                    'url'     =>  $ajaxOnChangeUrl,
                    'replace' => '#' . $mappingRulesDivId,
            ));
            return "$('#" . $id . "').unbind('change'); $('#" . $id . "').bind('change', function()
            {
                $ajaxSubmitScript
            }
            );";
        }

        /**
         * @param string $columnName
         * @return string
         */
        public static function resolveSampleColumnIdByColumnName($columnName)
        {
            assert('is_string($columnName)');
            return $columnName . '-import-data';
        }

        public static function getSampleColumnHeaderId()
        {
            return 'sample-column-header';
        }

        /**
         * Given a string, chop the string by 22 characters only displaying the first 22 characters with a '...'.
         * Add a div with a title, so that if the user hovers over the text, it will show the entire string.
         * @param string $value
         * @return string content
         */
        public static function renderChoppedStringContent($string)
        {
            if (strlen($string) <= 22)
            {
                return $string;
            }
            return ZurmoHtml::tag('div', array('title' => $string), substr($string, 0, 22) . '...');
        }
    }