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

    class ItemTest extends ZurmoBaseTest
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

        /**
         * This test serves to confirm createdBy/ModifiedBy user is setting/getting properly. This test was added
         * after the import work as part of a refactor to allow for readOnly attributes to be set externally.
         */
        public function testNewModelCreatedAndModifiedByUserIsNull()
        {
            $account = new Account();
            $this->assertTrue($account->createdByUser->id  < 0);
            $this->assertTrue($account->modifiedByUser->id < 0);
            $this->assertFalse(array_key_exists('createdByUser', $account->originalAttributeValues));
            $this->assertFalse(array_key_exists('modifiedByUser', $account->originalAttributeValues));

            //Add values and save. This account is being created.
            $account->name  = 'aTest';
            $account->owner = User::getByUsername('super');
            $this->assertTrue($account->save());
            $this->assertFalse(array_key_exists('createdByUser', $account->originalAttributeValues));
            $this->assertFalse(array_key_exists('modifiedByUser', $account->originalAttributeValues));
            $this->assertTrue($account->createdByUser->id  > 0);
            $this->assertTrue($account->modifiedByUser->id > 0);

            //Change the value and save. This time the account is being modified.
            $account->name  = 'aTestb';
            $this->assertTrue($account->save());
            $this->assertFalse(array_key_exists('modifiedByUser', $account->originalAttributeValues));
        }

        public function testCreationAndModificationTimes()
        {
            $user = UserTestHelper::createBasicUser('Billy');

            $account = new Account();
            $createdTime = time();
            $this->assertWithinTolerance($createdTime, DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->createdDateTime),  2);
            $this->assertWithinTolerance($createdTime, DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->modifiedDateTime), 2);
            sleep(3); // Sleeps are bad in tests, but I need some time to pass
                      // to test these time stamps. The 3's here just need to be more
                      // than the 2's in the asserts. Using 2 and 1 meant it failed
                      // occasionally because the second would tick over just at the
                      // wrong time.
            $account->owner = $user;
            $account->name = 'Test Account';
            $this->assertWithinTolerance($createdTime, DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->createdDateTime),  2);
            $this->assertWithinTolerance(time(),       DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->modifiedDateTime), 2);
            sleep(3);
            $account->officePhone = '1234567890';
            $lastModifiedTime = time();
            $this->assertWithinTolerance($createdTime,      DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->createdDateTime),  2);
            $this->assertWithinTolerance($lastModifiedTime, DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->modifiedDateTime), 2);
            $this->assertTrue($account->save());
            $this->assertWithinTolerance($createdTime,      DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->createdDateTime),  2);
            $this->assertWithinTolerance($lastModifiedTime, DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->modifiedDateTime), 2);
            $id = $account->id;
            unset($account);
            sleep(2);
            $account = Account::getById($id);
            $this->assertEquals('Test Account', $account->name);
            $this->assertEquals('1234567890',   $account->officePhone);
            $this->assertWithinTolerance($createdTime,      DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->createdDateTime),  2);
            $this->assertWithinTolerance($lastModifiedTime, DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->modifiedDateTime), 2);
            unset($account);
            sleep(3);
            $account = Account::getById($id);
            $this->assertEquals('Test Account', $account->name);
            $this->assertEquals('1234567890',   $account->officePhone);
            $this->assertWithinTolerance($createdTime,      DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->createdDateTime),  2);
            $this->assertWithinTolerance($lastModifiedTime, DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->modifiedDateTime), 2);
            unset($account);
            sleep(3);
            $account = Account::getById($id);
            $contact = ContactTestHelper::createContactByNameForOwner('contactTest', $user);
            $account->contacts->add($contact);
            $this->assertWithinTolerance($createdTime,      DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->createdDateTime),  2);
            $this->assertTrue($account->save());
            $this->assertWithinTolerance($createdTime,      DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->createdDateTime),  2);
            $this->assertNotEquals      ($lastModifiedTime, DateTimeUtil::convertDbFormatDateTimeToTimestamp($account->modifiedDateTime));
        }

        /**
         * @depends testCreationAndModificationTimes
         */
        public function testExtraItemsCreatedOnModelInstantiation()
        {
            $countBefore = intval(ZurmoRedBean::getCell("select count(*) from item;"));
            $account = new Account();
            $countAfter  = intval(ZurmoRedBean::getCell("select count(*) from item;"));
            $this->assertEquals($countBefore, $countAfter);
        }

        /**
         * @depends testCreationAndModificationTimes
         * @expectedException NotSupportedException
         */
         public function testItemReadOnlyFieldsCreatedTime()
         {
            $account = new Account();
            $createdTime = time();
            $this->assertTrue($account->isAttributeReadOnly('createdDateTime'));
            $account->createdDateTime = time() + 123123;
         }

        /**
         * @depends testCreationAndModificationTimes
         * @expectedException NotSupportedException
         */
         public function testItemReadOnlyFieldsCreatedUser()
         {
            $user = User::getByUsername('billy');
            $account = new Account();
            $this->assertTrue($account->isAttributeReadOnly('createdByUser'));
            $account->createdByUser = $user;
         }

        /**
         * @depends testCreationAndModificationTimes
         * @expectedException NotSupportedException
         */
         public function testItemReadOnlyFieldsModifiedTime()
         {
            $account = new Account();
            $createdTime = time();
            $this->assertTrue($account->isAttributeReadOnly('modifiedDateTime'));
            $account->modifiedDateTime = time() + 123123;
         }

        /**
         * @depends testCreationAndModificationTimes
         * @expectedException NotSupportedException
         */
         public function testItemReadOnlyFieldsModifiedUser()
         {
            $user = User::getByUsername('billy');
            $account = new Account();
            $account->modifiedByUser = $user;
         }

        /**
         * @depends testItemReadOnlyFieldsModifiedUser
         */
         public function testItemReadOnlyChangeScenarioSoCanPopulate()
         {
            Yii::app()->user->userModel = User::getByUsername('super');
            $dbDateTime1 = DateTimeUtil::convertTimestampToDbFormatDateTime(time() - 200);
            $dbDateTime2 = DateTimeUtil::convertTimestampToDbFormatDateTime(time() - 300);
            $dbDateTime3 = DateTimeUtil::convertTimestampToDbFormatDateTime(time() - 400);
            $jimmy = UserTestHelper::createBasicUser('Jimmy');
            $user  = User::getByUsername('billy');
            $account = new Account();
            $account->setScenario('importModel');
            $account->createdByUser    = $user;
            $account->modifiedByUser   = $user;
            $account->createdDateTime  = $dbDateTime1;
            $account->modifiedDateTime = $dbDateTime2;
            $account->owner            = Yii::app()->user->userModel;
            $account->name             = 'someName';
            $this->assertTrue($account->save());
            $accountId = $account->id;
            $account->forget();
            $account = Account::getById($accountId);
            $this->assertEquals($user, $account->createdByUser);
            $this->assertEquals($user, $account->modifiedByUser);
            $this->assertEquals($dbDateTime1, $account->createdDateTime);
            $this->assertEquals($dbDateTime2, $account->modifiedDateTime);
            $account->name = 'aNewName';
            $this->assertTrue($account->save());
            $account->forget();
            //Now test that the attempt to change createdByUser and modifiedUser on an existing model will not work.
            //even when there are read only override permissions set.
            $account = Account::getById($accountId);
            $this->assertEquals($user, $account->createdByUser);
            $this->assertEquals(Yii::app()->user->userModel, $account->modifiedByUser);
            $this->assertNotEquals($dbDateTime2, $account->modifiedDateTime);
            $this->assertNotEquals($dbDateTime3, $account->modifiedDateTime);
         }

        /**
         * @depends testItemReadOnlyChangeScenarioSoCanPopulate
         */
         public function testCreatedByAndModifiedByUsersPopulateCorrectly()
         {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $user = User::getByUsername('billy');
            $account = new Account();
            $account->name  = 'aNewDawn Inc';
            $account->owner = $user;
            $this->assertTrue($account->save());
            $account = Account::getById($account->id);
            $this->assertEquals($super, $account->createdByUser);
            $this->assertEquals($super, $account->modifiedByUser);
            Yii::app()->user->userModel = $user;
            $account->name = 'aSecondDawn Inc.';
            $this->assertTrue($account->save());
            $account = Account::getById($account->id);
            $this->assertEquals($super, $account->createdByUser);
            $this->assertEquals($user, $account->modifiedByUser);
         }

        public function testWorkflowsToProcessAfterSave()
        {
            $account  = new Account();
            $workflow = new Workflow();
            $workflow->setModuleClassName('something');
            $account->addWorkflowToProcessAfterSave($workflow);
            $workflows = $account->getWorkflowsToProcessAfterSave();
            $this->assertEquals(1, count($workflows));
            $this->assertEquals('something', $workflows[0]->getModuleClassName());
        }

        public function testReadOnlyFieldsOnSearchScenario()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $account                 = new Account(false);
            $account->setScenario('searchModel');
            $account->name           = 'aTestAccountForTestReadOnlyFieldsOnSearchScenario';
            $account->owner          = $super;
            $account->createdByUser  = $super;
            $account->modifiedByUser = $super;
            $account->validate();
            $this->assertFalse($account->hasErrors());
        }
    }
?>
