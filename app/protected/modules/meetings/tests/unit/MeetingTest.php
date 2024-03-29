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

    class MeetingTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $account = AccountTestHelper::createAccountByNameForOwner('anAccount', $super);
            MeetingTestHelper::createCategories();
        }

        public function testCreateAndGetMeetingById()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;

            $accounts = Account::getByName('anAccount');

            $contact1 = ContactTestHelper::createContactWithAccountByNameForOwner('superContact',  $super, $accounts[0]);
            $contact2 = ContactTestHelper::createContactWithAccountByNameForOwner('superContact2', $super, $accounts[0]);
            $contact3 = ContactTestHelper::createContactWithAccountByNameForOwner('superContact3', $super, $accounts[0]);
            $user                   = UserTestHelper::createBasicUser('Billy');
            $startStamp             = DateTimeUtil::convertTimestampToDbFormatDateTime(time()  + 10000);
            $endStamp               = DateTimeUtil::convertTimestampToDbFormatDateTime(time() + 11000);
            $meeting                   = new Meeting();
            $meeting->name             = 'MyMeeting';
            $meeting->owner            = $user;
            $meeting->location         = 'my location';
            $meeting->category->value  = 'Call';
            $meeting->startDateTime    = $startStamp;
            $meeting->endDateTime      = $endStamp;
            $meeting->description      = 'my test description';
            $meeting->activityItems->add($accounts[0]);
            $meeting->activityItems->add($contact1);
            $meeting->activityItems->add($contact2);
            $meeting->activityItems->add($contact3);
            $this->assertTrue($meeting->save());
            $id = $meeting->id;
            unset($meeting);
            $meeting = Meeting::getById($id);
            $this->assertEquals('MyMeeting',           $meeting->name);
            $this->assertEquals($startStamp,             $meeting->startDateTime);
            $this->assertEquals($endStamp,       $meeting->endDateTime);
            $this->assertEquals('my test description', $meeting->description);
            $this->assertEquals($user,                 $meeting->owner);
            $this->assertEquals(4, $meeting->activityItems->count());
            $this->assertEquals($accounts[0], $meeting->activityItems->offsetGet(0));
        }

        /**
         * @depends testCreateAndGetMeetingById
         */
        public function testGetLabel()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $meetings = Meeting::getByName('MyMeeting');
            $this->assertEquals(1, count($meetings));
            $this->assertEquals('Meeting',   $meetings[0]::getModelLabelByTypeAndLanguage('Singular'));
            $this->assertEquals('Meetings',  $meetings[0]::getModelLabelByTypeAndLanguage('Plural'));
        }

        /**
         * @depends testGetLabel
         */
        public function testGetMeetingsByNameForNonExistentName()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $meetings = Meeting::getByName('Test Meeting 69');
            $this->assertEquals(0, count($meetings));
        }

        /**
         * @depends testCreateAndGetMeetingById
         */
        public function testUpdateMeetingFromForm()
        {
            Yii::app()->user->userModel = User::getByUsername('super');

            $user = User::getByUsername('billy');
            $meetings = Meeting::getByName('MyMeeting');
            $meeting = $meetings[0];
            $this->assertEquals($meeting->name, 'MyMeeting');
            $timeStamp = time();
            $newStamp = DateTimeUtil::convertTimestampToDbFormatDateTime($timeStamp);
            $postData = array(
                'owner' => array(
                    'id' => $user->id,
                ),
                'name' => 'New Name',
                'startDateTime' => DateTimeUtil::convertTimestampToDisplayFormat(
                                            $timeStamp,
                                            DateTimeUtil::DATETIME_FORMAT_DATE_WIDTH,
                                            DateTimeUtil::DATETIME_FORMAT_TIME_WIDTH,
                                            true)
            );
            $sanitizedPostData = PostUtil::sanitizePostByDesignerTypeForSavingModel($meeting, $postData);
            $meeting->setAttributes($sanitizedPostData);
            $saved = $meeting->save();
            $this->assertTrue($saved);
            $id = $meeting->id;
            unset($meeting);
            $meeting = Meeting::getById($id);
            $this->assertEquals('New Name', $meeting->name);
            $this->assertEquals($sanitizedPostData['startDateTime'],  $meeting->startDateTime);

            //create new meeting from scratch where the startDateTime and endDateTime attributes are not populated.
            //It should let you save.
            $meeting = new Meeting();
            $postData = array(
                'owner' => array(
                    'id' => $user->id,
                ),
                'name' => 'Lamazing',
                'startDateTime' => DateTimeUtil::convertTimestampToDisplayFormat(
                                            $timeStamp,
                                            DateTimeUtil::DATETIME_FORMAT_DATE_WIDTH,
                                            DateTimeUtil::DATETIME_FORMAT_TIME_WIDTH,
                                            true)
            );
            $sanitizedPostData = PostUtil::sanitizePostByDesignerTypeForSavingModel($meeting, $postData);
            $meeting->setAttributes($sanitizedPostData);
            $saved = $meeting->save();
            $this->assertTrue($saved);
            $id = $meeting->id;
            unset($meeting);
            $meeting = Meeting::getById($id);
            $this->assertEquals('Lamazing', $meeting->name);
            $this->assertEquals($sanitizedPostData['startDateTime'],  $meeting->startDateTime);
            $this->assertEquals(null,       $meeting->endDateTime);
        }

        /**
         * @depends testUpdateMeetingFromForm
         */
        public function testDeleteMeeting()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $meetings = Meeting::getAll();
            $this->assertEquals(2, count($meetings));
            $meetings[0]->delete();
            $meetings = Meeting::getAll();
            $this->assertEquals(1, count($meetings));
        }

            /**
         * @depends testDeleteMeeting
         */
        public function testValidateBeforeAfterDateTimeValues()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            //First test with the start date time after the end date time which will produce a validation error.
            $meeting                   = new Meeting();
            $meeting->name             = 'MyMeeting';
            $meeting->owner            = Yii::app()->user->userModel;
            $meeting->location         = 'my location';
            $meeting->category->value  = 'Call';
            $meeting->startDateTime    = DateTimeUtil::convertTimestampToDbFormatDateTime(time()  + 11000);
            $meeting->endDateTime      = DateTimeUtil::convertTimestampToDbFormatDateTime(time()  + 10000);
            $meeting->description      = 'my test description';
            $saved                     = $meeting->save();
            $this->assertFalse($saved);
            //Now test with the start date time before the end date time.
            $meeting->startDateTime    = DateTimeUtil::convertTimestampToDbFormatDateTime(time()  + 9000);
            $meeting->endDateTime      = DateTimeUtil::convertTimestampToDbFormatDateTime(time()  + 10000);
            $saved                     = $meeting->save();
            $this->assertTrue($saved);
            //Now test with end date time equals '0000-00-00 00:00:00'.
            $meeting->endDateTime      = '0000-00-00 00:00:00';
            $saved                     = $meeting->save();
            $this->assertTrue($saved);
        }

            /**
         * @depends testValidateBeforeAfterDateTimeValues
         */
        public function testAutomatedLatestDateTimeChanges()
        {
            Yii::app()->user->userModel = User::getByUsername('super');

            //Creating a new meeting with a startDateTime. The latestDateTime should populate.
            $startStamp = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
            $meeting                = new Meeting();
            $meeting->name          = 'aTest';
            $meeting->startDateTime = $startStamp;
            $this->assertTrue($meeting->save());
            $this->assertEquals($startStamp, $meeting->startDateTime);
            $this->assertEquals($startStamp,   $meeting->latestDateTime);

            //Modify the meeting but do not change the startDateTime. The latestDateTime should not change.
            $meeting = Meeting::getById($meeting->id);
            $meeting->name = 'bTest';
            $this->assertTrue($meeting->save());
            $this->assertEquals($startStamp, $meeting->startDateTime);
            $this->assertEquals($startStamp,   $meeting->latestDateTime);

            //Modify the meeting and change the startDateTime. Confirm the latestDateTime has changed.
            $newStamp = DateTimeUtil::convertTimestampToDbFormatDateTime(time() + 1);
            $this->assertNotEquals($startStamp, $newStamp);
            $meeting                = Meeting::getById($meeting->id);
            $meeting->name          = 'bTest';
            $meeting->startDateTime = $newStamp;
            $this->assertTrue($meeting->save());
            $this->assertEquals($newStamp, $meeting->startDateTime);
            $this->assertEquals($newStamp, $meeting->latestDateTime);
        }

        public function testGetModelClassNames()
        {
            $modelClassNames = MeetingsModule::getModelClassNames();
            $this->assertEquals(1, count($modelClassNames));
            $this->assertEquals('Meeting', $modelClassNames[0]);
        }

        /**
         * Should not throw exception, should cast down ok
         */
        public function testCastDown()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $startStamp             = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
            $meeting                = new Meeting();
            $meeting->name          = 'aTest';
            $meeting->startDateTime = $startStamp;
            $this->assertTrue($meeting->save());
            $itemId = $meeting->getClassId('Item');
            $item = Item::getById($itemId);
            $modelDerivationPathToItem = RuntimeUtil::getModelDerivationPathToItem('Meeting');
            $castedDownModel        = $item->castDown(array($modelDerivationPathToItem));
            $this->assertEquals('Meeting', get_class($castedDownModel));
        }
    }
?>
