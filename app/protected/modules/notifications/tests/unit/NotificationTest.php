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

    class NotificationTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            UserTestHelper::createBasicUser('billy');
        }

        public function testGetCountByUser()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $this->assertEquals(0, Notification::getCountByUser(Yii::app()->user->userModel));
            $notification         = new Notification();
            $notification->type   = 'Simple';
            $notification->owner  = Yii::app()->user->userModel;
            $this->assertTrue($notification->save());
            $this->assertEquals(1, Notification::getCountByUser(Yii::app()->user->userModel));
            $this->assertTrue($notification->save());
            $notificationId = $notification->id;
            $notification->forget();

            //Retrieve again.
            $notification = Notification::getById($notificationId);
            $this->assertEquals('Simple', $notification->type);
            $this->assertEquals(1, Notification::getCountByUser(Yii::app()->user->userModel));

            $notification->delete();
            $this->assertEquals(0, Notification::getCountByUser(Yii::app()->user->userModel));
        }

        /**
         * @depends testGetCountByUser
         */
        public function testNotification()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $notification         = new Notification();
            $notification->type   = 'Simple';
            $notification->owner  = Yii::app()->user->userModel;
            $this->assertTrue($notification->save());
            $this->assertTrue($notification->save());
            $notificationId = $notification->id;
            $notification->forget();

            //Retrieve again.
            $notification = Notification::getById($notificationId);
            $this->assertEquals('Simple', $notification->type);
            $notification->delete();
        }

        /**
         * @depends testNotification
         */
        public function testNotificationMessage()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $message              = new NotificationMessage();
            $message->textContent = 'text content';
            $message->htmlContent = 'html content';
            $this->assertTrue($message->save());

            $messageId = $message->id;
            $message->forget();

            //Retrieve again.
            $message = NotificationMessage::getById($messageId);
            $this->assertEquals('text content', $message->textContent);
            $this->assertEquals('html content', $message->htmlContent);
        }

        /**
         * @depends testNotificationMessage
         */
        public function testGetCountByTypeAndUser()
        {
            $super = User::getByUsername('super');
            $billy = User::getByUsername('billy');
            Yii::app()->user->userModel = $super;
            $this->assertEquals(0, count(Notification::getAll()));

            $notification         = new Notification();
            $notification->type   = 'Simple';
            $notification->owner  = $super;
            $this->assertTrue($notification->save());
            $notification         = new Notification();
            $notification->type   = 'Simple';
            $notification->owner  = $super;
            $this->assertTrue($notification->save());

            //There are 2 notifications
            $this->assertEquals(2, count(Notification::getAll()));
            //And 0 notifications unread for billy
            $this->assertEquals(0, Notification::getCountByTypeAndUser('Simple', $billy));

            //Now add another super notification, but not simple.
            $notification         = new Notification();
            $notification->type   = 'Simple2Test';
            $notification->owner  = $super;
            $this->assertTrue($notification->save());
            //And there are still 2 notifications for super
            $this->assertEquals(2, Notification::getCountByTypeAndUser('Simple', $super));

            //Add a notification for billy.
            $notification = new Notification();
            $notification->type = 'Simple';
            $notification->owner = $billy;
            $this->assertTrue($notification->save());
            //And there is still 1 unread notification for billy
            $this->assertEquals(1, Notification::getCountByTypeAndUser('Simple', $billy));
        }

        /**
         * @depends testGetCountByTypeAndUser
         */
        public function testNonAdminCanCreateNotificationsAndMessages()
        {
            $super = User::getByUsername('super');
            $billy = User::getByUsername('billy');
            Yii::app()->user->userModel = $billy;

            //Billy can create a notification for billy
            $notification         = new Notification();
            $notification->type   = 'Simple';
            $notification->owner  = $billy;
            $this->assertTrue($notification->save());

            //And Billy can create a notification for super
            $notification         = new Notification();
            $notification->type   = 'Simple';
            $notification->owner  = $super;
            $this->assertTrue($notification->save());

            //Same with a message.
            $message              = new NotificationMessage();
            $message->textContent = 'text content2';
            $message->htmlContent = 'html content2';
            $this->assertTrue($message->save());
        }

        /**
         * @depends testNonAdminCanCreateNotificationsAndMessages
         */
        public function testRelationsBetweenNotificationAndNotificationMessage()
        {
            $super = User::getByUsername('super');
            $billy = User::getByUsername('billy');
            Yii::app()->user->userModel = $super;

            //Make sure the relations between Notification and NotificationMessage is working.
            $message              = new NotificationMessage();
            $message->textContent = 'text content2';
            $message->htmlContent = 'html content2';
            $this->assertTrue($message->save());

            $notification = new Notification();
            $notification->type                = 'SimpleYTest';
            $notification->owner               = $billy;
            $notification->notificationMessage = $message;
            $this->assertTrue($notification->save());

            //And Billy can create a notification for super
            $notification = new Notification();
            $notification->type                = 'SimpleZTest';
            $notification->owner               = $super;
            $notification->notificationMessage = $message;
            $this->assertTrue($notification->save());

            //At this point the message should have 2 notifications associated with it
            $messageId = $message->id;
            $message->forget();
            $message = NotificationMessage::getById($messageId);

            $this->assertEquals(2, $message->notifications->count());
            $this->assertTrue($message->notifications[0]->type == 'SimpleYTest' ||
                $message->notifications[0]->type == 'SimpleZTest');
            $this->assertTrue($message->notifications[1]->type == 'SimpleYTest' ||
                $message->notifications[1]->type == 'SimpleZTest');

            /** - Add back in if it is possible to get the NotificationMessages to Notifications as RedBeanModel::OWNED
             * //Currently it is not working and cause $this->assertEquals(2, $message->notifications->count());
             * to return 0.
            //When removing a notificationMessage with notifications, the notifications should be
            //removed too.
            $this->assertEquals(8, count(Notification::getAll()));
            $message->delete();
            $this->assertEquals(3, count(Notification::getAll()));
             **/
            $notifications = Notification::getByNotificationMessageId($messageId);
            $this->assertEquals(2, count($notifications));
        }

        /**
         * @depends testRelationsBetweenNotificationAndNotificationMessage
         */
        public function testDeleteByTypeAndUser()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $joe = UserTestHelper::createBasicUser('joe');
            $sally = UserTestHelper::createBasicUser('sally');

            //Make sure the relations between Notification and NotificationMessage is working.
            $message              = new NotificationMessage();
            $message->textContent = 'text content3';
            $message->htmlContent = 'html content3';
            $this->assertTrue($message->save());

            $notification1 = new Notification();
            $notification1->type                = 'SimpleDTest';
            $notification1->owner               = $joe;
            $notification1->notificationMessage = $message;
            $this->assertTrue($notification1->save());

            //And Billy can create a notification for super
            $notification2 = new Notification();
            $notification2->type                = 'SimpleDTest';
            $notification2->owner               = $sally;
            $notification2->notificationMessage = $message;
            $this->assertTrue($notification2->save());

            $this->assertEquals(2, $message->notifications->count());
            $messageId = $message->id;
            $notification1Id = $notification1->id;
            $notification2Id = $notification2->id;
            $message->forget();
            $notification1->forget();
            $notification2->forget();

            Notification::deleteByTypeAndUser('SimpleDTest', $joe);
            // Notification message should exist, because there is still notification point to it
            $message  = NotificationMessage::getById($messageId);
            $this->assertTrue($message instanceOf NotificationMessage);
            $notification2 = Notification::getById($notification2Id);
            $this->assertTrue($notification2 instanceOf Notification);
            $notifications = Notification::getByNotificationMessageId($messageId);
            $this->assertEquals(1, count($notifications));
            $this->assertEquals($notification2Id, $notifications[0]->id);

            try
            {
                Notification::getById($notification1Id);
                $this->fail();
            }
            catch (NotFoundException $e)
            {
            }
            $message->forget();
            $notification2->forget();
            // Now delete second notification, this time notification message should be deleted too
            Notification::deleteByTypeAndUser('SimpleDTest', $sally);
            try
            {
                NotificationMessage::getById($messageId);
                $this->fail();
            }
            catch (NotFoundException $e)
            {
            }
            try
            {
                Notification::getById($notification2Id);
                $this->fail();
            }
            catch (NotFoundException $e)
            {
            }
        }
    }
?>