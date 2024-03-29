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

    class WorkflowTriggersUtilTest extends WorkflowTriggersUtilBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            ContactsModule::loadStartingData();
        }

        public static function getDependentTestModelClassNames()
        {
            return array();
        }

        public function testResolveStructureToPHPString()
        {
            $this->assertEquals('1', WorkflowTriggersUtil::resolveStructureToPHPString('1'));
            $this->assertEquals('1 && 2', WorkflowTriggersUtil::resolveStructureToPHPString('1 AND 2'));
            $this->assertEquals('1 || 2', WorkflowTriggersUtil::resolveStructureToPHPString('1 OR 2'));
            $this->assertEquals('(1 || 2) && 3', WorkflowTriggersUtil::resolveStructureToPHPString('(1 OR 2) AND 3'));
            $this->assertEquals('1 && 2 && 3', WorkflowTriggersUtil::resolveStructureToPHPString('1 AND 2 AND 3'));
        }

        public function testResolveBooleansDataToPHPString()
        {
            $data = array(1 => true);
            $this->assertEquals('true', WorkflowTriggersUtil::resolveBooleansDataToPHPString('1', $data));

            $data = array(1 => true, 2 => false);
            $this->assertEquals('true && false', WorkflowTriggersUtil::resolveBooleansDataToPHPString('1 && false', $data));
        }

        /**
         * @expectedException NotSupportedException()
         */
        public function testResolveBooleansDataToPHPStringWithInvalidDataKey()
        {
            $data = array(1 => true);
            $this->assertEquals('true', WorkflowTriggersUtil::resolveBooleansDataToPHPString('1', $data));
        }

        /**
         * @expectedException NotSupportedException
         */
        public function testResolveBooleansDataToPHPStringWithInvalidDataValue()
        {
            $data = array(1 => null);
            $this->assertEquals('true', WorkflowTriggersUtil::resolveBooleansDataToPHPString('1', $data));
        }

        public function testEvaluatePHPString()
        {
            $method = new ReflectionMethod('WorkflowTriggersUtil', 'evaluatePHPString');
            $method->setAccessible(true);

            $this->assertTrue ($method->invokeArgs(new WorkflowTriggersUtil, array('true')));
            $this->assertFalse($method->invokeArgs(new WorkflowTriggersUtil, array('false')));
            $this->assertTrue ($method->invokeArgs(new WorkflowTriggersUtil, array('true && true')));
            $this->assertFalse($method->invokeArgs(new WorkflowTriggersUtil, array('true && false')));
            $this->assertTrue ($method->invokeArgs(new WorkflowTriggersUtil, array('true || false')));
            $this->assertTrue ($method->invokeArgs(new WorkflowTriggersUtil, array('true || false || true')));
            $this->assertFalse($method->invokeArgs(new WorkflowTriggersUtil, array('false || false || false')));
            $this->assertTrue ($method->invokeArgs(new WorkflowTriggersUtil, array('true && (true || false)')));
            $this->assertTrue ($method->invokeArgs(new WorkflowTriggersUtil, array('false || (true && true)')));
        }

        /**
         * @expectedException InvalidArgumentException
         */
        public function testEvaluatePHPStringThrowsException()
        {
            $method = new ReflectionMethod('WorkflowTriggersUtil', 'evaluatePHPString');
            $method->setAccessible(true);

            $this->assertTrue ($method->invokeArgs(new WorkflowTriggersUtil, array('hello')));
        }

        /**
         * @expectedException InvalidArgumentException
         */
        public function testEvaluatePHPStringThrowsFailedAssertion()
        {
            $method = new ReflectionMethod('WorkflowTriggersUtil', 'evaluatePHPString');
            $method->setAccessible(true);

            $this->assertTrue ($method->invokeArgs(new WorkflowTriggersUtil, array('( true || false')));
        }

        /**
         * Example of too much nesting, that would result in an exception
         * @expectedException NotSupportedException
         */
        public function testAreTriggersTrueBeforeSaveWithInvalidAttributeData()
        {
            $attributeIndexOrDerivedType = 'hasOne___hasMany2___primaryAddress___street1';
            $workflow = self::makeOnSaveWorkflowAndTriggerWithoutValueType($attributeIndexOrDerivedType, 'equals', 'aValue');
            $model           = new WorkflowModelTestItem();
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));
        }

        /**
         * Testing that when you have a relation with another owned relation before the attribute, that it works correctly.
         */
        public function testAreTriggersTrueBeforeSaveWithRelationAndAnotherRelationBeforeAttribute()
        {
            $attributeIndexOrDerivedType = 'hasMany2___primaryAddress___street1';
            $workflow      = self::makeOnSaveWorkflowAndTriggerWithoutValueType($attributeIndexOrDerivedType, 'equals', 'cValue',
                             'WorkflowsTestModule', 'WorkflowModelTestItem2');
            $model           = new WorkflowModelTestItem2();
            $relatedModel    = new WorkflowModelTestItem();
            $address                        = new Address();
            $relatedModel->primaryAddress          = $address;
            $relatedModel->primaryAddress->street1 = 'cValue';
            $model->hasMany2->add($relatedModel);
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));

            $model->hasMany2[0]->primaryAddress->street1 = 'bValue';
            $this->assertFalse(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));
        }

        public function testTriggerOnAnInferredModelWithASingleModelRelated()
        {
            $attributeIndexOrDerivedType = 'Account__activityItems__Inferred___name';
            $workflow        = self::makeOnSaveWorkflowAndTriggerWithoutValueType($attributeIndexOrDerivedType, 'equals', 'cValue',
                              'NotesModule', 'Note');
            $model           = new Note();
            $relatedModel    = new Account();
            $relatedModel->name = 'cValue';
            $model->activityItems->add($relatedModel);
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));

            $relatedModel->name = 'bValue';
            $this->assertTrue($relatedModel->save());
            $this->assertFalse(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));
        }

        /**
         * In the case of having both an account and contact related to the note, it should properly check the related
         * account and not the related contact.
         */
        public function testTriggerOnAnInferredModelWithAMultipleModelsRelated()
        {
            $attributeIndexOrDerivedType = 'Account__activityItems__Inferred___name';
            $workflow        = self::makeOnSaveWorkflowAndTriggerWithoutValueType($attributeIndexOrDerivedType, 'equals', 'cValue',
                               'NotesModule', 'Note');
            $model           = new Note();
            $model->description = 'description';
            $relatedModel    = new Account();
            $relatedModel->name = 'cValue';
            $model->activityItems->add($relatedModel);
            $relatedModel2    = new Contact();
            $relatedModel2->lastName = 'someLastName';
            $relatedModel2->state  = ContactsUtil::getStartingState();
            $model->activityItems->add($relatedModel2);
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));

            //Just testing that since nothing changed on the related model, it should still evaluate as true
            $relatedModel2->lastName = 'someLastName2';
            $this->assertTrue($relatedModel->save());
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));

            $relatedModel->name = 'bValue';
            $this->assertTrue($relatedModel->save());
            $this->assertFalse(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));
        }

        /**
         * @see testTriggerOnAnInferredModelWithAMultipleModelsRelated.  The difference here is that the model being
         * tested is the ownedModel which is a different if clause inside WorkflowTriggersUtil::isTriggerTrueByModel
         */
        public function testTriggerOnAnInferredModelsOwnedModelWithAMultipleModelsRelated()
        {
            $attributeIndexOrDerivedType = 'Account__activityItems__Inferred___billingAddress___street1';
            $workflow        = self::makeOnSaveWorkflowAndTriggerWithoutValueType($attributeIndexOrDerivedType, 'equals', 'cValue',
                               'NotesModule', 'Note');
            $model           = new Note();
            $relatedModel    = new Account();
            $relatedModel->name = 'dValue';
            $relatedModel->billingAddress->street1 = 'cValue';
            $model->activityItems->add($relatedModel);
            $relatedModel2    = new Opportunity();
            $relatedModel2->name = 'someName';
            $model->activityItems->add($relatedModel2);
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));

            //Just testing that since nothing changed on the related model, it should still evaluate as true
            $relatedModel2->name = 'someLastName2';
            $this->assertTrue($relatedModel->save());
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));

            $relatedModel->billingAddress->street1 = 'bValue';
            $this->assertTrue($relatedModel->save());
            $this->assertFalse(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));
        }

        /**
         * @see testTriggerOnAnInferredModelWithAMultipleModelsRelated. Saving Note first, then retrieving. This
         * means the activityItems are 'Item' model and need to be casted down.
         */
        public function testTriggerOnAnAlreadySavedInferredModelWithAMultipleModelsRelated()
        {
            $attributeIndexOrDerivedType = 'Account__activityItems__Inferred___name';
            $workflow        = self::makeOnSaveWorkflowAndTriggerWithoutValueType($attributeIndexOrDerivedType, 'equals', 'cValue',
                               'NotesModule', 'Note');
            $model           = new Note();
            $model->description = 'description';
            $relatedModel    = new Account();
            $relatedModel->name = 'cValue';
            $this->assertTrue($relatedModel->save());
            $model->activityItems->add($relatedModel);
            $relatedModel2    = new Contact();
            $relatedModel2->lastName = 'someLastName';
            $relatedModel2->state  = ContactsUtil::getStartingState();
            $this->assertTrue($relatedModel2->save());
            $model->activityItems->add($relatedModel2);
            $saved = $model->save();
            $this->assertTrue($saved);
            $modelId = $model->id;
            $model->forget();
            unset($model);

            $model = Note::getById($modelId);
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));

            //Just testing that since nothing changed on the related model, it should still evaluate as true
            $relatedModel2->lastName = 'someLastName2';
            $this->assertTrue($relatedModel->save());
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));

            $relatedModel->name = 'bValue';
            $this->assertTrue($relatedModel->save());
            $this->assertFalse(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));
        }

        /**
         * @see testTriggerOnAnInferredModelsOwnedModelWithAMultipleModelsRelated. Saving Note first, then retrieving. This
         * means the activityItems are 'Item' model and need to be casted down.
         */
        public function testTriggerOnAnAlreadySavedInferredModelsOwnedModelWithAMultipleModelsRelated()
        {
            $attributeIndexOrDerivedType = 'Account__activityItems__Inferred___billingAddress___street1';
            $workflow        = self::makeOnSaveWorkflowAndTriggerWithoutValueType($attributeIndexOrDerivedType, 'equals', 'cValue',
                               'NotesModule', 'Note');
            $model           = new Note();
            $model->description = 'description';
            $relatedModel    = new Account();
            $relatedModel->name = 'dValue';
            $relatedModel->billingAddress->street1 = 'cValue';
            $this->assertTrue($relatedModel->save());
            $model->activityItems->add($relatedModel);
            $currencies                    = Currency::getAll();
            $currencyValue                 = new CurrencyValue();
            $currencyValue->value          = 100;
            $currencyValue->currency       = $currencies[0];
            $relatedModel2                 = new Opportunity();
            $relatedModel2->name           = 'someName';
            $relatedModel2->amount         = $currencyValue;
            $relatedModel2->closeDate      = '2011-01-01';
            $relatedModel2->stage->value   = 'Verbal';
            $this->assertTrue($relatedModel2->save());
            $model->activityItems->add($relatedModel2);
            $saved = $model->save();
            $this->assertTrue($saved);
            $modelId = $model->id;
            $model->forget();
            unset($model);

            $model = Note::getById($modelId);
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));

            //Just testing that since nothing changed on the related model, it should still evaluate as true
            $relatedModel2->name = 'someLastName2';
            $this->assertTrue($relatedModel->save());
            $this->assertTrue(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));

            $relatedModel->billingAddress->street1 = 'bValue';
            $this->assertTrue($relatedModel->save());
            $this->assertFalse(WorkflowTriggersUtil::areTriggersTrueBeforeSave($workflow, $model));
        }
    }
?>