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

    class ImportUtilTest extends ImportBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            $jim = UserTestHelper::createBasicUser('jim');

            $values = array(
                'Multi 1',
                'Multi 2',
                'Multi 3',
            );
            $customFieldData = CustomFieldData::getByName('ImportTestMultiDropDown');
            $customFieldData->serializedData = serialize($values);
            $saved = $customFieldData->save();
            assert($saved);    // Not Coding Standard

            $values = array(
                'Cloud 1',
                'Cloud 2',
                'Cloud 3',
            );
            $customFieldData = CustomFieldData::getByName('ImportTestTagCloud');
            $customFieldData->serializedData = serialize($values);
            $saved = $customFieldData->save();
            assert($saved);    // Not Coding Standard
            ReadPermissionsOptimizationUtil::recreateTable(
                    ReadPermissionsOptimizationUtil::getMungeTableName('ImportModelTestItem'));
        }

        public static function getDependentTestModelClassNames()
        {
            return array('ImportModelTestItem', 'ImportModelTestItem2');
        }

        public function testResolveLinkMessageToModel()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $account = AccountTestHelper::createAccountByNameForOwner('account', Yii::app()->user->userModel);
            $content =  ImportUtil::resolveLinkMessageToModel($account);
            $this->assertFalse(strpos($content, 'accounts/default/details?id') === false);
            $contact = ContactTestHelper::createContactByNameForOwner('contact', Yii::app()->user->userModel);
            $content =  ImportUtil::resolveLinkMessageToModel($contact);
            $this->assertFalse(strpos($content, 'contacts/default/details?id') === false);
            $lead = LeadTestHelper::createLeadByNameForOwner('lead', Yii::app()->user->userModel);
            $content =  ImportUtil::resolveLinkMessageToModel($lead);
            $this->assertFalse(strpos($content, 'leads/default/details?id') === false);
        }

        public function testImportNameAndRelatedNameWithApostrophes()
        {
            Yii::app()->user->userModel = User::getByUsername('super');

            $testModels                        = ImportModelTestItem::getAll();
            $this->assertEquals(0, count($testModels));
            $import                                = new Import();
            $serializedData['importRulesType']     = 'ImportModelTestItem';
            $serializedData['firstRowIsHeaderRow'] = true;
            $import->serializedData                = serialize($serializedData);
            $this->assertTrue($import->save());

            ImportTestHelper::createTempTableByFileNameAndTableName('importApostropheTest.csv', $import->getTempTableName(), true);

            $this->assertEquals(3, ImportDatabaseUtil::getCount($import->getTempTableName())); // includes header rows.

            $mappingData = array(
                'column_0'   => ImportMappingUtil::makeStringColumnMappingData      ('string'),
                'column_1'   => ImportMappingUtil::makeStringColumnMappingData      ('lastName'),
                'column_2' => array('attributeIndexOrDerivedType' => 'hasOne', 'type' => 'importColumn',
                    'mappingRulesData' => array(
                        'RelatedModelValueTypeMappingRuleForm' =>
                        array('type' => RelatedModelValueTypeMappingRuleForm::ZURMO_MODEL_NAME))),
            );

            $importRules  = ImportRulesUtil::makeImportRulesByType('ImportModelTestItem');
            $page         = 0;
            $config       = array('pagination' => array('pageSize' => 50)); //This way all rows are processed.
            $dataProvider = new ImportDataProvider($import->getTempTableName(), true, $config);
            $dataProvider->getPagination()->setCurrentPage($page);
            $importResultsUtil = new ImportResultsUtil($import);
            $messageLogger     = new ImportMessageLogger();
            ImportUtil::importByDataProvider($dataProvider,
                $importRules,
                $mappingData,
                $importResultsUtil,
                new ExplicitReadWriteModelPermissions(),
                $messageLogger);
            $importResultsUtil->processStatusAndMessagesForEachRow();

            //Confirm that 2 ImportModelTestItem models were created and 2 ImportModelTestItem2 were created
            $testModels = ImportModelTestItem::getAll();
            $this->assertEquals(2, count($testModels));
            $this->assertEquals("Barrel'o Fun", $testModels[0]->lastName);
            $this->assertEquals('bLastName', $testModels[1]->lastName);
            $testModels = ImportModelTestItem2::getAll();
            $this->assertEquals(2, count($testModels));
            $this->assertEquals("D'Angelo Inc", $testModels[0]->name);
            $this->assertEquals('Dartmouth Financial Services', $testModels[1]->name);

            //Clear out data in table
            ZurmoRedBean::exec("delete from " . ImportModelTestItem::getTableName('ImportModelTestItem'));
        }

        /**
         * Test tag cloud and multi-select attribute import.
         * @depends testImportNameAndRelatedNameWithApostrophes
         */
        public function testSetDataAnalyzerMultiSelectAndTagCloudImport()
        {
            $super = User::getByUsername('super');
            $jim   = User::getByUsername('jim');
            Yii::app()->user->userModel = $jim;

            $testModels                        = ImportModelTestItem::getAll();
            $this->assertEquals(0, count($testModels));
            $import                                = new Import();
            $serializedData['importRulesType']     = 'ImportModelTestItem';
            $serializedData['firstRowIsHeaderRow'] = true;
            $import->serializedData                = serialize($serializedData);
            $this->assertTrue($import->save());

            ImportTestHelper::createTempTableByFileNameAndTableName('importMultiSelectDropDownTest.csv', $import->getTempTableName(), true);

            $this->assertEquals(6, ImportDatabaseUtil::getCount($import->getTempTableName())); // includes header rows.

            $multiDropDownInstructionsData    = array(CustomFieldsInstructionData::ADD_MISSING_VALUES =>
                                                              array('Multi 5', 'Multi 4'),
                                                              CustomFieldsInstructionData::MAP_MISSING_VALUES => array());
            $tagCloudInstructionsData         = array(CustomFieldsInstructionData::ADD_MISSING_VALUES =>
                                                              array('Cloud 5', 'Cloud 4'),
                                                              CustomFieldsInstructionData::MAP_MISSING_VALUES => array());
            $mappingData = array(
                'column_0'   => ImportMappingUtil::makeStringColumnMappingData      ('string'),
                'column_1'   => ImportMappingUtil::makeStringColumnMappingData      ('lastName'),
                'column_2'  => ImportMappingUtil::
                                makeMultiSelectDropDownColumnMappingData('multiDropDown', null,
                                                                         $multiDropDownInstructionsData),
                'column_3'  => ImportMappingUtil::
                                makeTagCloudColumnMappingData('tagCloud', null, $tagCloudInstructionsData)
            );

            $importRules  = ImportRulesUtil::makeImportRulesByType('ImportModelTestItem');
            $page         = 0;
            $config       = array('pagination' => array('pageSize' => 50)); //This way all rows are processed.
            $dataProvider = new ImportDataProvider($import->getTempTableName(), true, $config);
            $dataProvider->getPagination()->setCurrentPage($page);
            $importResultsUtil = new ImportResultsUtil($import);
            $messageLogger     = new ImportMessageLogger();
            ImportUtil::importByDataProvider($dataProvider,
                                             $importRules,
                                             $mappingData,
                                             $importResultsUtil,
                                             new ExplicitReadWriteModelPermissions(),
                                             $messageLogger);
            $importResultsUtil->processStatusAndMessagesForEachRow();

            //Confirm the missing custom field values were properly added.
            $customFieldData = CustomFieldData::getByName('ImportTestMultiDropDown');
            $values = array(
                'Multi 1',
                'Multi 2',
                'Multi 3',
                'Multi 4',
                'Multi 5',
            );
            $this->assertEquals($values, unserialize($customFieldData->serializedData));
            $customFieldData = CustomFieldData::getByName('ImportTestTagCloud');
                        $values = array(
                'Cloud 1',
                'Cloud 2',
                'Cloud 3',
                'Cloud 4',
                'Cloud 5',
            );
            $this->assertEquals($values, unserialize($customFieldData->serializedData));

            //Confirm that 5 models where created.
            $testModels = ImportModelTestItem::getAll();
            $this->assertEquals(5, count($testModels));

            foreach ($testModels as $model)
            {
                $this->assertEquals(array(Permission::NONE, Permission::NONE), $model->getExplicitActualPermissions ($jim));
            }

            //Confirm the values of the multi-select and tag cloud are as expected.
            $this->assertEquals(0, $testModels[2]->multiDropDown->values->count());
            $this->assertEquals(2, $testModels[2]->tagCloud->values->count());
            $this->assertEquals(2, $testModels[3]->multiDropDown->values->count());
            $this->assertEquals(0, $testModels[3]->tagCloud->values->count());

            $this->assertEquals(2, $testModels[1]->multiDropDown->values->count());
            $this->assertEquals('Multi 4', $testModels[1]->multiDropDown->values[0]->value);
            $this->assertEquals('Multi 2', $testModels[1]->multiDropDown->values[1]->value);
            $this->assertEquals(2, $testModels[1]->tagCloud->values->count());
            $this->assertEquals('Cloud 1', $testModels[1]->tagCloud->values[0]->value);
            $this->assertEquals('Cloud 4', $testModels[1]->tagCloud->values[1]->value);

            $this->assertEquals(2, $testModels[4]->multiDropDown->values->count());
            $this->assertEquals('Multi 1', $testModels[4]->multiDropDown->values[0]->value);
            $this->assertEquals('Multi 5', $testModels[4]->multiDropDown->values[1]->value);
            $this->assertEquals(2, $testModels[4]->tagCloud->values->count());
            $this->assertEquals('Cloud 5', $testModels[4]->tagCloud->values[0]->value);
            $this->assertEquals('Cloud 2', $testModels[4]->tagCloud->values[1]->value);
            //Confirm 10 rows were processed as 'created'.
            $this->assertEquals(5, ImportDatabaseUtil::getCount($import->getTempTableName(), "status = "
                                                                 . ImportRowDataResultsUtil::CREATED));

            //Confirm that 0 rows were processed as 'updated'.
            $this->assertEquals(0, ImportDatabaseUtil::getCount($import->getTempTableName(),  "status = "
                                                                 . ImportRowDataResultsUtil::UPDATED));

            //Confirm 0 rows were processed as 'errors'.
            $this->assertEquals(0, ImportDatabaseUtil::getCount($import->getTempTableName(),  "status = "
                                                                 . ImportRowDataResultsUtil::ERROR));

            $beansWithErrors = ImportDatabaseUtil::getSubset($import->getTempTableName(),     "status = "
                                                                 . ImportRowDataResultsUtil::ERROR);
            $this->assertEquals(0, count($beansWithErrors));

            //Clear out data in table
            ZurmoRedBean::exec("delete from " . ImportModelTestItem::getTableName('ImportModelTestItem'));
        }

        /**
         * Test when a normal user who can only view records he owns, tries to import records assigned to another user.
         * @depends testSetDataAnalyzerMultiSelectAndTagCloudImport
         */
        public function testImportSwitchingOwnerButShouldStillCreate()
        {
            $super = User::getByUsername('super');
            $jim   = User::getByUsername('jim');
            Yii::app()->user->userModel = $jim;

            //Confirm Jim can can only view ImportModelTestItems he owns.
            $item       = NamedSecurableItem::getByName('ImportModule');
            $this->assertEquals(Permission::NONE, $item->getEffectivePermissions($jim));

            $testModels                        = ImportModelTestItem::getAll();
            $this->assertEquals(0, count($testModels));
            $import                                = new Import();
            $serializedData['importRulesType']     = 'ImportModelTestItem';
            $serializedData['firstRowIsHeaderRow'] = true;
            $import->serializedData                = serialize($serializedData);
            $this->assertTrue($import->save());

            ImportTestHelper::createTempTableByFileNameAndTableName('importEmptyCurrencyTest.csv', $import->getTempTableName(), true);

            $this->assertEquals(3, ImportDatabaseUtil::getCount($import->getTempTableName())); // includes header rows.

            $columnMappingData         = array('attributeIndexOrDerivedType' => 'owner',
                                               'type'                        => 'extraColumn',
                                               'mappingRulesData'            => array(
                                                   'DefaultModelNameIdMappingRuleForm' =>
                                                   array('defaultModelId' => $super->id),
                                                   'UserValueTypeModelAttributeMappingRuleForm' =>
                                                   array('type' =>
                                                   UserValueTypeModelAttributeMappingRuleForm::ZURMO_USER_ID)));

            $mappingData = array(
                'column_0'  => ImportMappingUtil::makeStringColumnMappingData      ('lastName'),
                'column_1'  => ImportMappingUtil::makeStringColumnMappingData      ('string'),
                'column_2'  => $columnMappingData
            );

            $importRules  = ImportRulesUtil::makeImportRulesByType('ImportModelTestItem');
            $page         = 0;
            $config       = array('pagination' => array('pageSize' => 50)); //This way all rows are processed.
            $dataProvider = new ImportDataProvider($import->getTempTableName(), true, $config);
            $dataProvider->getPagination()->setCurrentPage($page);
            $importResultsUtil = new ImportResultsUtil($import);
            $messageLogger     = new ImportMessageLogger();
            ImportUtil::importByDataProvider($dataProvider,
                                             $importRules,
                                             $mappingData,
                                             $importResultsUtil,
                                             new ExplicitReadWriteModelPermissions(),
                                             $messageLogger);
            $importResultsUtil->processStatusAndMessagesForEachRow();

            //Confirm that 2 models where created.
            $testModels = ImportModelTestItem::getAll();
            $this->assertEquals(2, count($testModels));

            foreach ($testModels as $model)
            {
                $this->assertEquals(array(Permission::NONE, Permission::NONE), $model->getExplicitActualPermissions ($jim));
            }

            //Confirm 10 rows were processed as 'created'.
            $this->assertEquals(2, ImportDatabaseUtil::getCount($import->getTempTableName(), "status = "
                                                                 . ImportRowDataResultsUtil::CREATED));

            //Confirm that 0 rows were processed as 'updated'.
            $this->assertEquals(0, ImportDatabaseUtil::getCount($import->getTempTableName(),  "status = "
                                                                 . ImportRowDataResultsUtil::UPDATED));

            //Confirm 2 rows were processed as 'errors'.
            $this->assertEquals(0, ImportDatabaseUtil::getCount($import->getTempTableName(),  "status = "
                                                                 . ImportRowDataResultsUtil::ERROR));

            $beansWithErrors = ImportDatabaseUtil::getSubset($import->getTempTableName(),     "status = "
                                                                 . ImportRowDataResultsUtil::ERROR);
            $this->assertEquals(0, count($beansWithErrors));

            //Clear out data in table
            ZurmoRedBean::exec("delete from " . ImportModelTestItem::getTableName('ImportModelTestItem'));
        }

        /**
         * @depends testImportSwitchingOwnerButShouldStillCreate
         */
        public function testImportWithoutCurrencyValues()
        {
            Yii::app()->user->userModel = User::getByUsername('super');

            $testModels                        = ImportModelTestItem::getAll();
            $this->assertEquals(0, count($testModels));
            $import                                = new Import();
            $serializedData['importRulesType']     = 'ImportModelTestItem';
            $serializedData['firstRowIsHeaderRow'] = true;
            $import->serializedData                = serialize($serializedData);
            $this->assertTrue($import->save());

            ImportTestHelper::createTempTableByFileNameAndTableName('importEmptyCurrencyTest.csv', $import->getTempTableName(), true);

            $this->assertEquals(3, ImportDatabaseUtil::getCount($import->getTempTableName())); // includes header rows.

            $mappingData = array(
                'column_0'  => ImportMappingUtil::makeStringColumnMappingData      ('lastName'),
                'column_1'  => ImportMappingUtil::makeStringColumnMappingData      ('string')
            );

            $importRules  = ImportRulesUtil::makeImportRulesByType('ImportModelTestItem');
            $page         = 0;
            $config       = array('pagination' => array('pageSize' => 50)); //This way all rows are processed.
            $dataProvider = new ImportDataProvider($import->getTempTableName(), true, $config);
            $dataProvider->getPagination()->setCurrentPage($page);
            $importResultsUtil = new ImportResultsUtil($import);
            $messageLogger     = new ImportMessageLogger();
            ImportUtil::importByDataProvider($dataProvider,
                                             $importRules,
                                             $mappingData,
                                             $importResultsUtil,
                                             new ExplicitReadWriteModelPermissions(),
                                             $messageLogger);
            $importResultsUtil->processStatusAndMessagesForEachRow();

            //Confirm that 2 models where created.
            $testModels = ImportModelTestItem::getAll();
            $this->assertEquals(2, count($testModels));
            $jim = User::getByUsername('jim');
            foreach ($testModels as $model)
            {
                $this->assertEquals(array(Permission::NONE, Permission::NONE), $model->getExplicitActualPermissions ($jim));
            }

            //Confirm 10 rows were processed as 'created'.
            $this->assertEquals(2, ImportDatabaseUtil::getCount($import->getTempTableName(), "status = "
                                                                 . ImportRowDataResultsUtil::CREATED));

            //Confirm that 0 rows were processed as 'updated'.
            $this->assertEquals(0, ImportDatabaseUtil::getCount($import->getTempTableName(),  "status = "
                                                                 . ImportRowDataResultsUtil::UPDATED));

            //Confirm 2 rows were processed as 'errors'.
            $this->assertEquals(0, ImportDatabaseUtil::getCount($import->getTempTableName(),  "status = "
                                                                 . ImportRowDataResultsUtil::ERROR));

            $beansWithErrors = ImportDatabaseUtil::getSubset($import->getTempTableName(),     "status = "
                                                                 . ImportRowDataResultsUtil::ERROR);
            $this->assertEquals(0, count($beansWithErrors));

            //Confirm the base code is USD
            $this->assertEquals('USD', Yii::app()->currencyHelper->getBaseCode());

            //Creating an object produces the correct currency code.
            $testItem           = new ImportModelTestItem();
            $this->assertEquals('USD', $testItem->currencyValue->currency->code);
            $testItem->string   = 'test';
            $testItem->lastName = 'testAlso';
            $this->assertTrue($testItem->save());
            $testItemId         = $testItem->id;
            $testItem->forget();

            //The currency code, even though not set, shows up correctly based on the base code.
            $testItem = ImportModelTestItem::getById($testItemId);
            $this->assertEquals('USD', $testItem->currencyValue->currency->code);

            //Test that the related currency information is not empty for the imported objects.
            $this->assertEquals('USD', $testModels[0]->currencyValue->currency->code);

            //Clear out data in table
            ZurmoRedBean::exec("delete from " . ImportModelTestItem::getTableName('ImportModelTestItem'));
        }

        /**
         * @depends testImportWithoutCurrencyValues
         */
        public function testSimpleImportWithStringAndFullNameWhichAreRequiredAttributeOnImportTestModelItem()
        {
            Yii::app()->user->userModel = User::getByUsername('super');

            $testModels                        = ImportModelTestItem::getAll();
            $this->assertEquals(0, count($testModels));
            $import                                = new Import();
            $serializedData['importRulesType']     = 'ImportModelTestItem';
            $serializedData['firstRowIsHeaderRow'] = true;
            $import->serializedData                = serialize($serializedData);
            $this->assertTrue($import->save());

            ImportTestHelper::createTempTableByFileNameAndTableName('importAnalyzerTest.csv', $import->getTempTableName(), true);

            $this->assertEquals(13, ImportDatabaseUtil::getCount($import->getTempTableName())); // includes header rows.

            $mappingData = array(
                'column_0' => array('attributeIndexOrDerivedType' => 'string',        'type' => 'importColumn',
                                    'mappingRulesData' => array(
                                        'DefaultValueModelAttributeMappingRuleForm' =>
                                        array('defaultValue' => null))),
                'column_23' => array('attributeIndexOrDerivedType' => 'FullName',     'type' => 'importColumn',
                                    'mappingRulesData' => array(
                                        'FullNameDefaultValueModelAttributeMappingRuleForm' =>
                                        array('defaultValue' => null))),
                                        );

            $importRules  = ImportRulesUtil::makeImportRulesByType('ImportModelTestItem');
            $page         = 0;
            $config       = array('pagination' => array('pageSize' => 50)); //This way all rows are processed.
            $dataProvider = new ImportDataProvider($import->getTempTableName(), true, $config);
            $dataProvider->getPagination()->setCurrentPage($page);
            $importResultsUtil = new ImportResultsUtil($import);
            $messageLogger     = new ImportMessageLogger();
            ImportUtil::importByDataProvider($dataProvider,
                                             $importRules,
                                             $mappingData,
                                             $importResultsUtil,
                                             new ExplicitReadWriteModelPermissions(),
                                             $messageLogger);
            $importResultsUtil->processStatusAndMessagesForEachRow();

            //Confirm that 10 models where created.
            $testModels = ImportModelTestItem::getAll();
            $this->assertEquals(10, count($testModels));
            $jim = User::getByUsername('jim');
            foreach ($testModels as $model)
            {
                $this->assertEquals(array(Permission::NONE, Permission::NONE), $model->getExplicitActualPermissions ($jim));
            }

            //Confirm 10 rows were processed as 'created'.
            $this->assertEquals(10, ImportDatabaseUtil::getCount($import->getTempTableName(), "status = "
                                                                 . ImportRowDataResultsUtil::CREATED));

            //Confirm that 0 rows were processed as 'updated'.
            $this->assertEquals(0, ImportDatabaseUtil::getCount($import->getTempTableName(),  "status = "
                                                                 . ImportRowDataResultsUtil::UPDATED));

            //Confirm 2 rows were processed as 'errors'.
            $this->assertEquals(2, ImportDatabaseUtil::getCount($import->getTempTableName(),  "status = "
                                                                 . ImportRowDataResultsUtil::ERROR));

            $beansWithErrors = ImportDatabaseUtil::getSubset($import->getTempTableName(),     "status = "
                                                                 . ImportRowDataResultsUtil::ERROR);
            $this->assertEquals(2, count($beansWithErrors));

            //Confirm the messages are as expected.
            $compareMessages = array(
                'Import - Last Name specified is too long.',
                'Import - Last Name - Last Name cannot be blank.',
            );
            $this->assertEquals($compareMessages, unserialize(current($beansWithErrors)->serializedMessages));

            $compareMessages = array(
                'Import - String This field is required and neither a value nor a default value was specified.',
                'Import - Full name value required, but missing.',
                'Import - Last Name - Last Name cannot be blank.',
                'Import - String - String cannot be blank.',
            );
            $this->assertEquals($compareMessages, unserialize(next($beansWithErrors)->serializedMessages));

            //Clear out data in table
            ZurmoRedBean::exec("delete from " . ImportModelTestItem::getTableName('ImportModelTestItem'));
        }

        /**
         * @depends testSimpleImportWithStringAndFullNameWhichAreRequiredAttributeOnImportTestModelItem
         */
        public function testSettingExplicitReadWriteModelPermissionsDuringImport()
        {
            Yii::app()->user->userModel = User::getByUsername('super');

            $testModels = ImportModelTestItem::getAll();
            $this->assertEquals(0, count($testModels));

            //Add a read only user for import. Then all models should be readable by jim in addition to super.
            $explicitReadWriteModelPermissions = new ExplicitReadWriteModelPermissions();
            $explicitReadWriteModelPermissions->addReadOnlyPermitable(User::getByUsername('jim'));

            $testModels                        = ImportModelTestItem::getAll();
            $this->assertEquals(0, count($testModels));
            $import                                = new Import();
            $serializedData['importRulesType']     = 'ImportModelTestItem';
            $serializedData['firstRowIsHeaderRow'] = true;
            $import->serializedData                = serialize($serializedData);
            $this->assertTrue($import->save());

            ImportTestHelper::createTempTableByFileNameAndTableName('importAnalyzerTest.csv', $import->getTempTableName(), true);

            $this->assertEquals(13, ImportDatabaseUtil::getCount($import->getTempTableName())); // includes header rows.

            $mappingData = array(
                'column_0' => array('attributeIndexOrDerivedType' => 'string',        'type' => 'importColumn',
                                    'mappingRulesData' => array(
                                        'DefaultValueModelAttributeMappingRuleForm' =>
                                        array('defaultValue' => null))),
                'column_23' => array('attributeIndexOrDerivedType' => 'FullName',     'type' => 'importColumn',
                                    'mappingRulesData' => array(
                                        'FullNameDefaultValueModelAttributeMappingRuleForm' =>
                                        array('defaultValue' => null))),
                                        );

            $importRules  = ImportRulesUtil::makeImportRulesByType('ImportModelTestItem');
            $page         = 0;
            $config       = array('pagination' => array('pageSize' => 3)); //This way all rows are processed.
            $dataProvider = new ImportDataProvider($import->getTempTableName(), true, $config);
            $dataProvider->getPagination()->setCurrentPage($page);
            $importResultsUtil = new ImportResultsUtil($import);
            $messageLogger     = new ImportMessageLogger();
            ImportUtil::importByDataProvider($dataProvider,
                                             $importRules,
                                             $mappingData,
                                             $importResultsUtil,
                                             $explicitReadWriteModelPermissions,
                                             $messageLogger);
            $importResultsUtil->processStatusAndMessagesForEachRow();

            //Confirm that 3 models where created.
            $testModels = ImportModelTestItem::getAll();
            $this->assertEquals(3, count($testModels));
            $jim = User::getByUsername('jim');
            foreach ($testModels as $model)
            {
                $this->assertEquals(array(Permission::READ, Permission::NONE), $model->getExplicitActualPermissions ($jim));
            }

            //Clear out data in table
            ZurmoRedBean::exec("delete from " . ImportModelTestItem::getTableName('ImportModelTestItem'));

            //Now test with read/write permissions being set.
            $explicitReadWriteModelPermissions = new ExplicitReadWriteModelPermissions();
            $explicitReadWriteModelPermissions->addReadWritePermitable(User::getByUsername('jim'));
            $dataProvider = new ImportDataProvider($import->getTempTableName(), true, $config);
            $dataProvider->getPagination()->setCurrentPage($page);
            $importResultsUtil = new ImportResultsUtil($import);
            $messageLogger     = new ImportMessageLogger();
            ImportUtil::importByDataProvider($dataProvider,
                                             $importRules,
                                             $mappingData,
                                             $importResultsUtil,
                                             $explicitReadWriteModelPermissions,
                                             $messageLogger);
            $importResultsUtil->processStatusAndMessagesForEachRow();

            //Confirm that 3 models where created.
            $testModels = ImportModelTestItem::getAll();
            $this->assertEquals(3, count($testModels));
            $jim = User::getByUsername('jim');
            foreach ($testModels as $model)
            {
                $this->assertEquals(array(Permission::READ_WRITE_CHANGE_PERMISSIONS_CHANGE_OWNER, Permission::NONE), $model->getExplicitActualPermissions ($jim));
            }
        }
    }
?>