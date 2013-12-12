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

    class ZurmoModuleTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
        }

        public function setUp()
        {
            parent::setUp();
            Yii::app()->user->userModel = User::getByUsername('super');
        }

        public function testGetModelClassNames()
        {
            $modelClassNames = ZurmoModule::getModelClassNames();
            $this->assertEquals(24, count($modelClassNames));
            $this->assertEquals('Address', $modelClassNames[0]);
            $this->assertEquals('AuditEvent', $modelClassNames[1]);
            $this->assertEquals('BaseStarredModel', $modelClassNames[2]);
            $this->assertEquals('Currency', $modelClassNames[3]);
            $this->assertEquals('CurrencyValue', $modelClassNames[4]);
            $this->assertEquals('Email', $modelClassNames[5]);
            $this->assertEquals('ExplicitReadWriteModelPermissions', $modelClassNames[6]);
            $this->assertEquals('FileModel', $modelClassNames[7]);
            $this->assertEquals('Group', $modelClassNames[8]);
            $this->assertEquals('Item', $modelClassNames[9]);
            $this->assertEquals('NamedSecurableItem', $modelClassNames[10]);
            $this->assertEquals('OwnedCustomField', $modelClassNames[11]);
            $this->assertEquals('OwnedModel', $modelClassNames[12]);
            $this->assertEquals('OwnedMultipleValuesCustomField', $modelClassNames[13]);
            $this->assertEquals('OwnedSecurableItem', $modelClassNames[14]);
            $this->assertEquals('Permission', $modelClassNames[15]);
            $this->assertEquals('Permitable', $modelClassNames[16]);
            $this->assertEquals('Person', $modelClassNames[17]);
            $this->assertEquals('Policy', $modelClassNames[18]);
            $this->assertEquals('Right', $modelClassNames[19]);
            $this->assertEquals('Role', $modelClassNames[20]);
            $this->assertEquals('SavedSearch', $modelClassNames[21]);
            $this->assertEquals('SecurableItem', $modelClassNames[22]);
            $this->assertEquals('ZurmoModelSearch', $modelClassNames[23]);
        }
    }
?>