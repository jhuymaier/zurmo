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

    class ImportSanitizerUtilTest extends ImportBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            $super                      = SecurityTestHelper::createSuperAdmin();
            Yii::app()->user->userModel = $super;
            Yii::app()->timeZoneHelper->load();
            $values = array(
                'Test1',
                'Test2',
                'Test3',
                'Sample',
                'Demo',
            );
            $customFieldData = CustomFieldData::getByName('ImportTestDropDown');
            $customFieldData->serializedData = serialize($values);
            $saved = $customFieldData->save();
            assert($saved); // Not Coding Standard
            Currency::getAll(); //forces base currency to be created.
        }

        public static function getDependentTestModelClassNames()
        {
            return array('ImportModelTestItem',
                            'ImportModelTestItem2',
                            'ImportModelTestItem3');
        }

        public function testCurrencySanitizationUsingNumberSanitizerUtil()
        {
            $currency = Currency::getByCode(Yii::app()->currencyHelper->getBaseCode());

            //Test a pure number as the value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = ImportMappingUtil::makeCurrencyColumnMappingData('currencyValue', $currency);
            $sanitizerUtilTypes        = CurrencyValueAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'currencyValue', '500.34',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('500.34', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test with dollar signs. Should strip out dollar signs
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = ImportMappingUtil::makeCurrencyColumnMappingData('currencyValue', $currency);
            $sanitizerUtilTypes        = CurrencyValueAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'currencyValue', '$500.34',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('500.34', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test with commmas. Should strip out commas
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = ImportMappingUtil::makeCurrencyColumnMappingData('currencyValue', $currency);
            $sanitizerUtilTypes        = CurrencyValueAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'currencyValue', '15,500.34', // Not Coding Standard
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('15500.34', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
        }

        public function testSanitizeValueBySanitizerTypesForBooleanTypeThatIsNotRequired()
        {
            //Test a non-required boolean with no value or default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = CheckBoxAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'boolean', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required boolean with no value, but a valid default value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '1')));
            $sanitizerUtilTypes        = CheckBoxAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'boolean', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(true, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required boolean with a valid value, and a default value. The valid value should come through.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '0')));
            $sanitizerUtilTypes        = CheckBoxAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'boolean', 'yes',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(true, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required boolean with a valid value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = CheckBoxAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'boolean', 'yes',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(true, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required boolean with a value that is not a valid mapped value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = CheckBoxAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'boolean', 'blah',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null,    $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Boolean Invalid check box format.';
            $this->assertEquals($compareMessage, $messages[0]);

            //Test a non-required boolean with a value that is invalidly mapped and a specified default value. The specified
            //default value should be ignored in this scenario.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '1')));
            $sanitizerUtilTypes        = CheckBoxAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'boolean', 'blah',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Boolean Invalid check box format.';
            $this->assertEquals($compareMessage, $messages[0]);

            //Test a non-required boolean with a valid mapped value of 'no' where it evaluates to false, and a default
            //value of '1'.  The default value should be ignored and the resulting sanitized value should be false.
            //Test a non-required boolean with a value that is invalidly mapped and a specified default value. The specified
            //default value should be ignored in this scenario.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '1')));
            $sanitizerUtilTypes        = CheckBoxAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'boolean', 'no',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(false, $sanitizedValue);
            $this->assertTrue($sanitizedValue !== null);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
        }

        public function testSanitizeValueBySanitizerTypesForDateTypeThatIsNotRequired()
        {
            //Test a non-required date with no value or a default value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy')));
            $sanitizerUtilTypes        = DateAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'date', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required date with no value but a default value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '2010-05-04'),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy')));
            $sanitizerUtilTypes        = DateAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'date', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('2010-05-04', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required date with a value and a default value.  The default value will be ignored.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '2010-05-04'),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy')));
            $sanitizerUtilTypes        = DateAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'date', '02-20-2005',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('2005-02-20', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required date with an invalid value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy')));
            $sanitizerUtilTypes        = DateAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'date', '02-2005-06',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $this->assertEquals(0, count($messages));

            //Test a non-required date with an invalid value and a default value which will not be used since the
            //first sanitization of the date format will fail.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '2010-05-04'),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy')));
            $sanitizerUtilTypes        = DateAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'date', '02-2005-06',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $this->assertEquals(0, count($messages));
        }

        public function testSanitizeValueBySanitizerTypesForDateTimeTypeThatIsNotRequired()
        {
            //Test a non-required dateTime with no value or a default value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy hh:mm')));
            $sanitizerUtilTypes        = DateTimeAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dateTime', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required dateTime with no value but a default value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '2010-05-04 05:00'),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy hh:mm')));
            $sanitizerUtilTypes        = DateTimeAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dateTime', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('2010-05-04 05:00', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required dateTime with a value and a default value.  The default value will be ignored.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '2010-05-04 00:00'),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy hh:mm:ss')));
            $sanitizerUtilTypes        = DateTimeAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dateTime', '02-20-2005 04:22:00',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('2005-02-20 04:22:00', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required dateTime with an invalid value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy hh:mm')));
            $sanitizerUtilTypes        = DateTimeAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dateTime', '02-2005-06',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $this->assertEquals(0, count($messages));

            //Test a non-required dateTime with an invalid value and a default value which will not be used since the
            //first sanitization of the datetime format will fail.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '2010-05-04 00:00'),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy hh:mm')));
            $sanitizerUtilTypes        = DateTimeAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dateTime', '02-2005-06',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $this->assertEquals(0, count($messages));

            //Test the createdDateTime with a value and a default value.  The default value will be ignored.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '2010-05-04 00:00'),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy hh:mm:ss')));
            $sanitizerUtilTypes        = DateTimeAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'createdDateTime', '02-20-2005 04:22:00',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('2005-02-20 04:22:00', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test the modifiedDateTime with a value and a default value.  The default value will be ignored.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '2010-05-04 00:00'),
                                               'ValueFormatMappingRuleForm'                =>
                                               array('format' => 'MM-dd-yyyy hh:mm:ss')));
            $sanitizerUtilTypes        = DateTimeAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'modifiedDateTime', '02-20-2005 04:22:00',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('2005-02-20 04:22:00', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
        }

        public function testSanitizeValueBySanitizerTypesForDropDownTypeThatIsNotRequired()
        {
            $customFieldsInstructionData = array(CustomFieldsInstructionData::ADD_MISSING_VALUES => array());

            //Test a non-required dropDown with no value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueDropDownModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)),
                                               'customFieldsInstructionData' => $customFieldsInstructionData);
            $sanitizerUtilTypes        = DropDownAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dropDown', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required dropDown with no value and a default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueDropDownModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'Test1')),
                                               'customFieldsInstructionData' => $customFieldsInstructionData);
            $sanitizerUtilTypes        = DropDownAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dropDown', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('Test1', $sanitizedValue->value);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required dropDown with a valid value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueDropDownModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'Test2')),
                                               'customFieldsInstructionData' => $customFieldsInstructionData);
            $sanitizerUtilTypes        = DropDownAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dropDown', 'Demo',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('Demo', $sanitizedValue->value);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required email with a missing value and a default value.  The default value should not
            //be picked up, it should be ignored.  On the first sanitization failure, sanitization will stop, this is
            //why the default value is not set.
            //Since there are no missing value instructions, the sanitization will result in an error message.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueDropDownModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'Test3')),
                                               'customFieldsInstructionData' => $customFieldsInstructionData);
            $sanitizerUtilTypes        = DropDownAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dropDown', 'NotThere',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Drop Down Pick list value specified is missing from existing pick ' .
                              'list and no valid instructions were provided on how to resolve this.';
            $this->assertEquals($compareMessage, $messages[0]);

            //Now use a value that is missing, but there are instructions to add it, and confirm it is added.
            $customFieldsInstructionData = array(CustomFieldsInstructionData::ADD_MISSING_VALUES => array('NewValue'));
            $customFieldData = CustomFieldData::getByName('ImportTestDropDown');
            $this->assertEquals(5, count(unserialize($customFieldData->serializedData)));
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueDropDownModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'Test1')),
                                               'customFieldsInstructionData' => $customFieldsInstructionData);
            $sanitizerUtilTypes        = DropDownAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dropDown', 'NewValue',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals('NewValue', $sanitizedValue->value);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
            $customFieldData = CustomFieldData::getByName('ImportTestDropDown');
            $values = unserialize($customFieldData->serializedData);
            $this->assertEquals(6, count($values));
            $this->assertEquals('NewValue', $values[5]);

            //Now use a value that is missing, but there is valid instructions on how to map it. Use different casing
            //to increase test coverage. (sample instead of Sample)
            $customFieldsInstructionData = array(CustomFieldsInstructionData::ADD_MISSING_VALUES => array(),
                                                 CustomFieldsInstructionData::MAP_MISSING_VALUES => array('MappedValue' => 'sample'));
            $customFieldData = CustomFieldData::getByName('ImportTestDropDown');
            $this->assertEquals(6, count(unserialize($customFieldData->serializedData)));
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueDropDownModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'Test1')),
                                               'customFieldsInstructionData' => $customFieldsInstructionData);
            $sanitizerUtilTypes        = DropDownAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dropDown', 'MappedValue',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('Sample', $sanitizedValue->value);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
            $customFieldData = CustomFieldData::getByName('ImportTestDropDown');
            $values = unserialize($customFieldData->serializedData);
            $this->assertEquals(6, count($values));

            //Now use a value that is missing, there are instructions on how to map it, but the mapping is invalid.
            $customFieldsInstructionData = array(CustomFieldsInstructionData::ADD_MISSING_VALUES => array(),
                                                 CustomFieldsInstructionData::MAP_MISSING_VALUES => array('MappedValue' => 'SampleX'));
            $customFieldData = CustomFieldData::getByName('ImportTestDropDown');
            $this->assertEquals(6, count(unserialize($customFieldData->serializedData)));
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueDropDownModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'Test1')),
                                               'customFieldsInstructionData' => $customFieldsInstructionData);
            $sanitizerUtilTypes        = DropDownAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dropDown', 'MappedValue',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Drop Down Pick list value specified is missing from existing pick list, ' .
                              'has a specified mapping value, but the mapping value is not a valid value.';
            $this->assertEquals($compareMessage, $messages[0]);
            $customFieldData = CustomFieldData::getByName('ImportTestDropDown');
            $values = unserialize($customFieldData->serializedData);
            $this->assertEquals(6, count($values));

            //Test using no value, and relying on the defaultValue to populate
            $customFieldsInstructionData = array(CustomFieldsInstructionData::ADD_MISSING_VALUES => array(),
                                                 CustomFieldsInstructionData::MAP_MISSING_VALUES => array());
            $customFieldData = CustomFieldData::getByName('ImportTestDropDown');
            $this->assertEquals(6, count(unserialize($customFieldData->serializedData)));
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueDropDownModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'Test1')),
                                               'customFieldsInstructionData' => $customFieldsInstructionData);
            $sanitizerUtilTypes        = DropDownAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'dropDown', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('Test1', $sanitizedValue->value);
            $this->assertTrue($sanitizedValue instanceof OwnedCustomField);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
            $customFieldData = CustomFieldData::getByName('ImportTestDropDown');
            $values = unserialize($customFieldData->serializedData);
            $this->assertEquals(6, count($values));
        }

        public function testSanitizeValueBySanitizerTypesForEmailTypeThatIsNotRequired()
        {
            //Test a non-required email with no value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = EmailAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'Email', 'emailAddress', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required email with no value and a default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'a@a.com')));
            $sanitizerUtilTypes        = EmailAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'Email', 'emailAddress', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('a@a.com', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required email with a valid value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'b@b.com')));
            $sanitizerUtilTypes        = EmailAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'Email', 'emailAddress', 'c@c.com',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('c@c.com', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required email with an invalid value and a default value.  The default value should not
            //be picked up, it should be ignored.  On the first sanitization failure, sanitization will stop, this is
            //why the default value is not set.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'c@c.com')));
            $sanitizerUtilTypes        = EmailAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'Email', 'emailAddress', 'abcxco@',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Email - Email Address Invalid email format.';
            $this->assertEquals($compareMessage, $messages[0]);
        }

        public function testSanitizeValueBySanitizerTypesForFullNameTypeThatIsRequired()
        {
            //Test a non-required FullName with no value or default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'FullNameDefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = FullNameAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null, null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Full name value required, but missing.';
            $this->assertEquals($compareMessage, $messages[0]);

            //Test a non-required FullName with no value, but a valid default value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'FullNameDefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'something valid')));
            $sanitizerUtilTypes        = FullNameAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null, null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('something valid', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required FullName with a valid value, and a default value. The valid value should come through.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'FullNameDefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'something valid')));
            $sanitizerUtilTypes        = FullNameAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null, 'aValue',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('aValue', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required FullName with a valid value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'FullNameDefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = FullNameAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null, 'first last',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('first last', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required FullName with a value that is too long and no specified default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'FullNameDefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = FullNameAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $value                     = self::getStringByLength(85);
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null, $value,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Last Name specified is too long.';
            $this->assertEquals($compareMessage, $messages[0]);

            //Test a non-required FullName with a value that is too long and a specified default value. The specified
            //default value should be ignored in this scenario.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'FullNameDefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'willNotMatter')));
            $sanitizerUtilTypes        = FullNameAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $value                     = self::getStringByLength(85);
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null, $value,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Last Name specified is too long.';
            $this->assertEquals($compareMessage, $messages[0]);

            //A first name that is too large, but the last name is ok.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'FullNameDefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'willNotMatter')));
            $sanitizerUtilTypes        = FullNameAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $value                     = self::getStringByLength(85) . ' okLastName';
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null, $value,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - First Name specified is too long.';
            $this->assertEquals($compareMessage, $messages[0]);
        }

        public function testSanitizeValueBySanitizerTypesForModelDerivedIdTypeThatNotIsRequired()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $importModelTestItem3Model1 = ImportTestHelper::createImportModelTestItem3('aaa');
            $importModelTestItem3Model2 = ImportTestHelper::createImportModelTestItem3('bbb');
            $importModelTestItem3Model3 = ImportTestHelper::createImportModelTestItem3('ccc');

            //Update the external system id.

            ExternalSystemIdUtil::addExternalIdColumnIfMissing(RedBeanModel::getTableName('ImportModelTestItem3'));
            $externalSystemIdColumnName = ExternalSystemIdUtil::EXTERNAL_SYSTEM_ID_COLUMN_NAME;
            ZurmoRedBean::exec("update " . ImportModelTestItem3::getTableName('ImportModelTestItem3')
            . " set $externalSystemIdColumnName = 'Q' where id = {$importModelTestItem3Model3->id}");

            //Test a non-required related model with an invalid value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'IdValueTypeMappingRuleForm' =>
                                               array('type' => IdValueTypeMappingRuleForm::ZURMO_MODEL_ID)));
            $sanitizerUtilTypes        = ImportModelTestItem3DerivedAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null, 'qweqwe',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - ImportModelTestItem3 ID specified did not match any existing records.';
            $this->assertEquals($compareMessage, $messages[0]);

            //Test a non-required related model with no value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'IdValueTypeMappingRuleForm' =>
                                               array('type' => IdValueTypeMappingRuleForm::ZURMO_MODEL_ID)));
            $sanitizerUtilTypes        = ImportModelTestItem3DerivedAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null, null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required related model with a valid zurmo model id
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'IdValueTypeMappingRuleForm' =>
                                               array('type' => IdValueTypeMappingRuleForm::ZURMO_MODEL_ID)));
            $sanitizerUtilTypes        = ImportModelTestItem3DerivedAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null,
                                         $importModelTestItem3Model2->id,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($importModelTestItem3Model2, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required related model with a valid external system id
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'IdValueTypeMappingRuleForm' =>
                                               array('type' => IdValueTypeMappingRuleForm::EXTERNAL_SYSTEM_ID)));
            $sanitizerUtilTypes        = ImportModelTestItem3DerivedAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', null, 'Q',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($importModelTestItem3Model3, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
        }

        public function testSanitizeValueBySanitizerTypesForNumberTypesThatAreaNotRequired()
        {
            //Test a non-required decimal with no value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = DecimalAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'float', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required decimal with no value and a default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '45.65')));
            $sanitizerUtilTypes        = DecimalAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'float', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(45.65, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required decimal with a valid value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '45.65')));
            $sanitizerUtilTypes        = DecimalAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'float', '23.67',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('23.67', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
            //Now try with a correctly casted value.
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'float', 23.67,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(23.67, $sanitizedValue);
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
            //Now try an integer for a float. This should work ok.
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'float', 25,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(25, $sanitizedValue);
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required decimal with an invalid value and a default value.  The default value should not
            //be picked up, it should be ignored.  On the first sanitization failure, sanitization will stop, this is
            //why the default value is not set.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '45.65')));
            $sanitizerUtilTypes        = DecimalAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'float', 'abc',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Float Invalid number format.';
            $this->assertEquals($compareMessage, $messages[0]);

            ///////////////////////
            //Now test Integer
            //Test a non-required integer with no value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = IntegerAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'integer', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required integer with no value and a default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '41')));
            $sanitizerUtilTypes        = IntegerAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'integer', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(41, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required integer with a valid value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '2342')));
            $sanitizerUtilTypes        = IntegerAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'integer', '34',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('34', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
            //Now try with a correctly casted value.
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'integer', 654,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(654, $sanitizedValue);
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
            //Now try a float for an integer. This should not work ok
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'integer', 25.54,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));

            //Test a non-required integer with an invalid value and a default value.  The default value should not
            //be picked up, it should be ignored.  On the first sanitization failure, sanitization will stop, this is
            //why the default value is not set.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => '45')));
            $sanitizerUtilTypes        = IntegerAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'integer', 'abc',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Integer Invalid integer format.';
            $this->assertEquals($compareMessage, $messages[0]);
        }

        public function testSanitizeValueBySanitizerTypesForRelatedModelIdTypeThatNotIsRequired()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $importModelTestItem2Model1 = ImportTestHelper::createImportModelTestItem2('aaa');
            $importModelTestItem2Model2 = ImportTestHelper::createImportModelTestItem2('bbb');
            $importModelTestItem2Model3 = ImportTestHelper::createImportModelTestItem2('ccc');

            //Update the external system id.
            ExternalSystemIdUtil::addExternalIdColumnIfMissing(RedBeanModel::getTableName('ImportModelTestItem2'));
            $externalSystemIdColumnName = ExternalSystemIdUtil::EXTERNAL_SYSTEM_ID_COLUMN_NAME;
            ZurmoRedBean::exec("update " . ImportModelTestItem2::getTableName('ImportModelTestItem2')
            . " set $externalSystemIdColumnName = 'R' where id = {$importModelTestItem2Model3->id}");

            //Test a non-required related model with an invalid value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'RelatedModelValueTypeMappingRuleForm' =>
                                               array('type' => RelatedModelValueTypeMappingRuleForm::ZURMO_MODEL_ID)));
            $sanitizerUtilTypes        = ImportModelTestItem2AttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'hasOne', 'qweqwe',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Has One ID specified did not match any existing records.';
            $this->assertEquals($compareMessage, $messages[0]);

            //Test a non-required related model with no value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'RelatedModelValueTypeMappingRuleForm' =>
                                               array('type' => RelatedModelValueTypeMappingRuleForm::ZURMO_MODEL_ID)));
            $sanitizerUtilTypes        = ImportModelTestItem2AttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'hasOne', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required related model with a valid zurmo model id
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'RelatedModelValueTypeMappingRuleForm' =>
                                               array('type' => RelatedModelValueTypeMappingRuleForm::ZURMO_MODEL_ID)));
            $sanitizerUtilTypes        = ImportModelTestItem2AttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'hasOne', $importModelTestItem2Model2->id,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($importModelTestItem2Model2, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required related model with a valid external system id
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'RelatedModelValueTypeMappingRuleForm' =>
                                               array('type' => RelatedModelValueTypeMappingRuleForm::EXTERNAL_SYSTEM_ID)));
            $sanitizerUtilTypes        = ImportModelTestItem2AttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'hasOne', 'R',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($importModelTestItem2Model3, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required related model with a valid model name
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'RelatedModelValueTypeMappingRuleForm' =>
                                               array('type' => RelatedModelValueTypeMappingRuleForm::ZURMO_MODEL_NAME)));
            $sanitizerUtilTypes        = ImportModelTestItem2AttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'hasOne', 'bbb',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($importModelTestItem2Model2, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required related model with a model name for a new model.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'RelatedModelValueTypeMappingRuleForm' =>
                                               array('type' => RelatedModelValueTypeMappingRuleForm::ZURMO_MODEL_NAME)));
            $sanitizerUtilTypes        = ImportModelTestItem2AttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'hasOne', 'rrr',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('rrr', $sanitizedValue->name);
            $this->assertEquals('ImportModelTestItem2', get_class($sanitizedValue));
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
        }

        public function testSanitizeValueBySanitizerTypesForSelfIdTypeThatIsRequired()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $importModelTestItem1Model1 = ImportTestHelper::createImportModelTestItem('aaa', 'xxxx');
            $importModelTestItem1Model2 = ImportTestHelper::createImportModelTestItem('bbb', 'yyyy');
            $importModelTestItem1Model3 = ImportTestHelper::createImportModelTestItem('ccc', 'zzzz');

            //Update the external system id.
            ExternalSystemIdUtil::addExternalIdColumnIfMissing(RedBeanModel::getTableName('ImportModelTestItem'));
            $externalSystemIdColumnName = ExternalSystemIdUtil::EXTERNAL_SYSTEM_ID_COLUMN_NAME;
            ZurmoRedBean::exec("update " . ImportModelTestItem::getTableName('ImportModelTestItem')
            . " set $externalSystemIdColumnName = 'J' where id = {$importModelTestItem1Model3->id}");

            //Test the id attribute with an invalid value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'IdValueTypeMappingRuleForm' =>
                                               array('type' => IdValueTypeMappingRuleForm::ZURMO_MODEL_ID)));
            $sanitizerUtilTypes        = IdAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'id', 'xasdasd',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Id ID specified did not match any existing records.';
            $this->assertEquals($compareMessage, $messages[0]);

            //Test the id attribute with no value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'IdValueTypeMappingRuleForm' =>
                                               array('type' => IdValueTypeMappingRuleForm::ZURMO_MODEL_ID)));
            $sanitizerUtilTypes        = IdAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'id', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a required string with a valid zurmo model id
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'IdValueTypeMappingRuleForm' =>
                                               array('type' => IdValueTypeMappingRuleForm::ZURMO_MODEL_ID)));
            $sanitizerUtilTypes        = IdAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'id', $importModelTestItem1Model2->id,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($importModelTestItem1Model2->id, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a required string with a valid external system id
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'IdValueTypeMappingRuleForm' =>
                                               array('type' => IdValueTypeMappingRuleForm::EXTERNAL_SYSTEM_ID)));
            $sanitizerUtilTypes        = IdAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'id', 'J',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($importModelTestItem1Model3->id, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
        }

        public function testSanitizeValueBySanitizerTypesForStringTypeThatIsRequired()
        {
            //Test a required string with no value or default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = TextAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'string', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - String This field is required and neither a value' .
                              ' nor a default value was specified.';
            $this->assertEquals($compareMessage, $messages[0]);

            //Test a required string with no value, but a valid default value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'something valid')));
            $sanitizerUtilTypes        = TextAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'string', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('something valid', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a required string with a valid value, and a default value. The valid value should come through.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'something valid')));
            $sanitizerUtilTypes        = TextAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'string', 'aValue',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('aValue', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a required string with a valid value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = TextAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'string', 'bValue',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('bValue', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a required string with a value that is too long and no specified default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = TextAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $value                     = self::getStringByLength(85);
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'string', $value,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(substr($value, 0, 64), $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a required string with a value that is too long and a specified default value. The specified
            //default value should be ignored in this scenario.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'willNotMatter')));
            $sanitizerUtilTypes        = TextAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $value                     = self::getStringByLength(85);
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'string', $value,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(substr($value, 0, 64), $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
        }

        public function testSanitizeValueBySanitizerTypesForStringTypeThatIsNotRequired()
        {
            //Test a non-required phone with no value or default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = PhoneAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'phone', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required phone with no value, but a valid default value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'something valid')));
            $sanitizerUtilTypes        = PhoneAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'phone', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('something valid', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required phone with a valid value, and a default value. The valid value should come through.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'something valid')));
            $sanitizerUtilTypes        = PhoneAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'phone', 'aValue',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('aValue', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required phone with a valid value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = PhoneAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'phone', 'bValue',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('bValue', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required phone with a value that is too long and no specified default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = PhoneAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $value                     = self::getStringByLength(85);
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'phone', $value,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(substr($value, 0, 14), $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required phone with a value that is too long and a specified default value. The specified
            //default value should be ignored in this scenario.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'willNotMatter')));
            $sanitizerUtilTypes        = PhoneAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $value                     = self::getStringByLength(85);
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'phone', $value,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(substr($value, 0, 14), $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
        }

        public function testSanitizeValueBySanitizerTypesForTextAreaTypeThatIsNotRequired()
        {
            //Test a non-required textArea with no value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn');
            $sanitizerUtilTypes        = TextAreaAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'textArea', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required textArea with a valid value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn');
            $sanitizerUtilTypes        = TextAreaAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'textArea', 'bValue',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('bValue', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required textArea with a value that is too long.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn');
            $sanitizerUtilTypes        = TextAreaAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $value                     = self::getStringByLength(65070);
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'textArea', $value,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(substr($value, 0, 65000), $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required textArea with no value, but a valid default value
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type'             => 'importColumn',
                                               'mappingRulesData' => array(
                                                    'DefaultValueModelAttributeMappingRuleForm' =>
                                                            array('defaultValue' => 'something valid')));
            $sanitizerUtilTypes        = TextAreaAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                sanitizeValueBySanitizerTypes(
                    $sanitizerUtilTypes, 'ImportModelTestItem', 'textArea', null,
                    'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('something valid', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
        }

        public function testSanitizeValueBySanitizerTypesForUrlTypeThatIsNotRequired()
        {
            //Test a non-required email with no value and no default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => null)));
            $sanitizerUtilTypes        = UrlAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'url', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required email with no value and a default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'abc.com')));
            $sanitizerUtilTypes        = UrlAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'url', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('abc.com', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required email with a valid value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'def.com')));
            $sanitizerUtilTypes        = UrlAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'url', 'gre.com',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals('http://gre.com', $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a non-required email with an invalid value and a default value.  The default value should not
            //be picked up, it should be ignored.  On the first sanitization failure, sanitization will stop, this is
            //why the default value is not set.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultValueModelAttributeMappingRuleForm' =>
                                               array('defaultValue' => 'ggggga.com')));
            $sanitizerUtilTypes        = UrlAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'url', 'abcxco@',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals(null, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Url Invalid url format.';
            $this->assertEquals($compareMessage, $messages[0]);
        }

        public function testSanitizeValueBySanitizerTypesForUserTypeThatIsRequired()
        {
            $billy = UserTestHelper::createBasicUser('billy');
            $jimmy = UserTestHelper::createBasicUser('jimmy');
            $sally = UserTestHelper::createBasicUser('sally');

            //Update the external system id.
            ExternalSystemIdUtil::addExternalIdColumnIfMissing(RedBeanModel::getTableName('User'));
            $externalSystemIdColumnName = ExternalSystemIdUtil::EXTERNAL_SYSTEM_ID_COLUMN_NAME;
            ZurmoRedBean::exec("update " . User::getTableName('User')
            . " set $externalSystemIdColumnName = 'K' where id = {$jimmy->id}");

            //Test a required user with no value or default value.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultModelNameIdMappingRuleForm' =>
                                               array('defaultModelId' => null),
                                               'UserValueTypeModelAttributeMappingRuleForm' =>
                                               array('type' =>
                                               UserValueTypeModelAttributeMappingRuleForm::ZURMO_USER_ID)));
            $sanitizerUtilTypes        = UserAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'owner', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertNull($sanitizedValue);
            $this->assertFalse($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(1, count($messages));
            $compareMessage = 'Import - Owner Id is required and was not specified.';
            $this->assertEquals($compareMessage, $messages[0]);

            //Test a required string with no value, but a valid default value, a user id.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultModelNameIdMappingRuleForm' =>
                                               array('defaultModelId' => $billy->id),
                                               'UserValueTypeModelAttributeMappingRuleForm' =>
                                               array('type' =>
                                               UserValueTypeModelAttributeMappingRuleForm::ZURMO_USER_ID)));
            $sanitizerUtilTypes        = UserAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'owner', null,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($billy, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a required string with a valid user id.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultModelNameIdMappingRuleForm' =>
                                               array('defaultModelId' => null),
                                               'UserValueTypeModelAttributeMappingRuleForm' =>
                                               array('type' =>
                                               UserValueTypeModelAttributeMappingRuleForm::ZURMO_USER_ID)));
            $sanitizerUtilTypes        = UserAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'owner', $billy->id,
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($billy, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a required string with a valid external system user id.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultModelNameIdMappingRuleForm' =>
                                               array('defaultModelId' => null),
                                               'UserValueTypeModelAttributeMappingRuleForm' =>
                                               array('type' =>
                                               UserValueTypeModelAttributeMappingRuleForm::EXTERNAL_SYSTEM_USER_ID)));
            $sanitizerUtilTypes        = UserAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'owner', 'K',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($jimmy, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));

            //Test a required string with a valid username.
            $importSanitizeResultsUtil = new ImportSanitizeResultsUtil();
            $columnMappingData         = array('type' => 'importColumn', 'mappingRulesData' => array(
                                               'DefaultModelNameIdMappingRuleForm' =>
                                               array('defaultModelId' => null),
                                               'UserValueTypeModelAttributeMappingRuleForm' =>
                                               array('type' =>
                                               UserValueTypeModelAttributeMappingRuleForm::ZURMO_USERNAME)));
            $sanitizerUtilTypes        = UserAttributeImportRules::getSanitizerUtilTypesInProcessingOrder();
            $sanitizedValue            = ImportSanitizerUtil::
                                         sanitizeValueBySanitizerTypes(
                                         $sanitizerUtilTypes, 'ImportModelTestItem', 'owner', 'sally',
                                         'column_0', $columnMappingData, $importSanitizeResultsUtil);
            $this->assertEquals($sally, $sanitizedValue);
            $this->assertTrue($importSanitizeResultsUtil->shouldSaveModel());
            $messages = $importSanitizeResultsUtil->getMessages();
            $this->assertEquals(0, count($messages));
        }
    }
?>