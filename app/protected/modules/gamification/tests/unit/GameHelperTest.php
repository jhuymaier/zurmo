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

    class GameHelperTest extends ZurmoBaseTest
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

        public function tearDown()
        {
            parent::tearDown();
            Yii::app()->gameHelper->resetDeferredPointTypesAndValuesByUserIdToAdd();
        }

        public function testTriggerSearchModelsEvent()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $models = GameScore::getAllByPersonIndexedByType(Yii::app()->user->userModel);
            $this->assertEquals(0, count($models));
            Yii::app()->gameHelper->triggerSearchModelsEvent('Account');
            $models = GameScore::getAllByPersonIndexedByType(Yii::app()->user->userModel);
            $this->assertEquals(1, count($models));
            $this->assertEquals('SearchAccount', $models['SearchAccount']->type);
        }

        /**
         * @depends testTriggerSearchModelsEvent
         */
        public function testTriggerMassEditEvent()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $models = GameScore::getAllByPersonIndexedByType(Yii::app()->user->userModel);
            $this->assertEquals(1, count($models));
            Yii::app()->gameHelper->triggerMassEditEvent('Account');
            $models = GameScore::getAllByPersonIndexedByType(Yii::app()->user->userModel);
            $this->assertEquals(2, count($models));
            $this->assertEquals('MassEditAccount', $models['MassEditAccount']->type);
        }

        /**
         * @depends testTriggerMassEditEvent
         */
        public function testTriggerImportEvent()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $models = GameScore::getAllByPersonIndexedByType(Yii::app()->user->userModel);
            $this->assertEquals(2, count($models));
            Yii::app()->gameHelper->triggerImportEvent('Account');
            $models = GameScore::getAllByPersonIndexedByType(Yii::app()->user->userModel);
            $this->assertEquals(3, count($models));
            $this->assertEquals('ImportAccount', $models['ImportAccount']->type);
        }

        /**
         * @depends testTriggerImportEvent
         */
        public function testAddPointsByUserDeferred()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $this->assertEquals(array(), Yii::app()->gameHelper->getDeferredPointTypesAndValuesByUserIdToAdd());
            $pointTypeAndValueData = array('SomeThing' => 50);
            Yii::app()->gameHelper->addPointsByUserDeferred($super, 'SomeThing', 50);
            $compareData = array(Yii::app()->user->userModel->id => $pointTypeAndValueData);
            $this->assertEquals($compareData, Yii::app()->gameHelper->getDeferredPointTypesAndValuesByUserIdToAdd());
        }

        /**
         * @depends testAddPointsByUserDeferred
         */
        public function testProcessDeferredPoints()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $this->assertEquals(array(), Yii::app()->gameHelper->getDeferredPointTypesAndValuesByUserIdToAdd());
            $pointTypeAndValueData = array(GamePoint::TYPE_USER_ADOPTION => 50);
            Yii::app()->gameHelper->addPointsByUserDeferred($super, GamePoint::TYPE_USER_ADOPTION, 50);
            $compareData = array(Yii::app()->user->userModel->id => $pointTypeAndValueData);
            $this->assertEquals($compareData, Yii::app()->gameHelper->getDeferredPointTypesAndValuesByUserIdToAdd());
            Yii::app()->gameHelper->processDeferredPoints();
        }

        public function testArraySum()
        {
            $data = array('type1' => 50, 'type2' => 30);
            $this->assertEquals(80, array_sum($data));
        }

        /**
         * @depends testProcessDeferredPoints
         */
        public function testResolveLevelChange()
        {
            $super                       = User::getByUsername('super');
            Yii::app()->user->userModel  = $super;
            $billy                       = UserTestHelper::createBasicUser('Billy');
            Yii::app()->user->userModel  = $billy;
            $this->assertEquals(0, count(GameNotification::getAllByUser($billy)));

            //test user at general level 0 where they dont have enough points to move up (No game notification created)
            $gamePoint = new GamePoint();
            $gamePoint->type = GamePoint::TYPE_USER_ADOPTION;
            $gamePoint->value = 100;
            $gamePoint->person = $billy;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(0, count(GameNotification::getAllByUser($billy)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(100, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_GENERAL, $billy);
            $this->assertTrue($gameLevel->id < 0);
            $this->assertEquals(1, $gameLevel->value);

            //test user at general level 0 where they do have enough points to move up   (Game notification created)
            $gamePoint->value = 250;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(1, count(GameNotification::getAllByUser($billy)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(250, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_GENERAL, $billy);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(1, $gameLevel->value);

            //test user at general level 1 where they dont have enough points to move up (No game notification created)
            $gamePoint->value = 350;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(1, count(GameNotification::getAllByUser($billy)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(350, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_GENERAL, $billy);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(1, $gameLevel->value);

            //test user at general level 1 where they do have enough points to move up   (Game notification created)
            $gamePoint->value = 575;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(2, count(GameNotification::getAllByUser($billy)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(575, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_GENERAL, $billy);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(2, $gameLevel->value);

            //test user at general level 50 with 700 000 points, so there is nowhere to move up to (No game notification created)
            $gamePoint->value = 700000;
            $this->assertTrue($gamePoint->save());
            $gameLevel->value = 50;
            $this->assertTrue($gameLevel->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(2, count(GameNotification::getAllByUser($billy)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(700000, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_GENERAL, $billy);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(50, $gameLevel->value);
        }

        /**
         * @depends testResolveLevelChange
         * This will also implicitly test sub-level level changes.
         */
        public function testResolveLevelChangeBonusPointsAndSubLevels()
        {
            $billy                       = User::getByUsername('billy');
            Yii::app()->user->userModel  = $billy;
            $this->assertEquals(2, count(GameNotification::getAllByUser($billy)));

            $gamePoint2 = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(700000, $gamePoint2->value);

            //test user at general level 0 where they dont have enough points to move up (No game notification created)
            $gamePoint = new GamePoint();
            $gamePoint->type = GamePoint::TYPE_NEW_BUSINESS;
            $gamePoint->value = 50;
            $gamePoint->person = $billy;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(2, count(GameNotification::getAllByUser($billy)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_NEW_BUSINESS, $billy);
            $this->assertEquals(50, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_NEW_BUSINESS, $billy);
            $this->assertTrue($gameLevel->id < 0);
            $this->assertEquals(1, $gameLevel->value);
            $gamePoint2 = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(700000, $gamePoint2->value);

            //test user at general level 0 where they do have enough points to move up (No game notification created)
            $gamePoint->value = 105;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(2, count(GameNotification::getAllByUser($billy)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_NEW_BUSINESS, $billy);
            $this->assertEquals(105, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_NEW_BUSINESS, $billy);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(1, $gameLevel->value);
            $gamePoint2 = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(700100, $gamePoint2->value);

            //test user at general level 1 where they dont have enough points to move up (No game notification created)
            $gamePoint->value = 150;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(2, count(GameNotification::getAllByUser($billy)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_NEW_BUSINESS, $billy);
            $this->assertEquals(150, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_NEW_BUSINESS, $billy);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(1, $gameLevel->value);
            $gamePoint2 = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(700100, $gamePoint2->value);

            //test user at general level 1 where they do have enough points to move up (No game notification created)
            $gamePoint->value = 250;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(2, count(GameNotification::getAllByUser($billy)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_NEW_BUSINESS, $billy);
            $this->assertEquals(250, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_NEW_BUSINESS, $billy);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(2, $gameLevel->value);
            $gamePoint2 = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(700210, $gamePoint2->value);

            //test user at general level 50 with 700 000 points, so there is nowhere to move up to (No game notification created)
            $gamePoint->value = 700000;
            $this->assertTrue($gamePoint->save());
            $gameLevel->value = 7;
            $this->assertTrue($gameLevel->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(2, count(GameNotification::getAllByUser($billy)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_NEW_BUSINESS, $billy);
            $this->assertEquals(700000, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_NEW_BUSINESS, $billy);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(7, $gameLevel->value);
            $gamePoint2 = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $billy);
            $this->assertEquals(700210, $gamePoint2->value);
        }

        /**
         * @depends testResolveLevelChangeBonusPointsAndSubLevels
         */
        public function testResolveNewBadges()
        {
            Yii::app()->gameHelper->resolveNewBadges();
        }

         /**
         * Tests the global configuration to enable/disable modalNotifications
         */
        public function testGamificationModalNotificationsGlobalConfiguration()
        {
            $super                       = User::getByUsername('super');
            Yii::app()->user->userModel  = $super;
            $scot                        = UserTestHelper::createBasicUser('Scot');
            Yii::app()->user->userModel  = $scot;
            $this->assertEquals(0, count(GameNotification::getAllByUser($scot)));

            //test user at general level 0 where they do have enough points to move up   (Game notification created)
            $gamePoint = new GamePoint();
            $gamePoint->type = GamePoint::TYPE_USER_ADOPTION;
            $gamePoint->person = $scot;
            $gamePoint->value = 300;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(1, count(GameNotification::getAllByUser($scot)));
            $gamePoint = GamePoint::resolveToGetByTypeAndPerson(GamePoint::TYPE_USER_ADOPTION, $scot);
            $this->assertEquals(300, $gamePoint->value);
            $gameLevel = GameLevel::resolveByTypeAndPerson(GameLevel::TYPE_GENERAL, $scot);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(1, $gameLevel->value);

            //test user at general level 1 where they do have enough points to move up   (No game notification created)
            ZurmoConfigurationUtil::setByModuleName('ZurmoModule', 'gamificationModalNotificationsEnabled', false);
            $gamePoint = new GamePoint();
            $gamePoint->type = GamePoint::TYPE_USER_ADOPTION;
            $gamePoint->person = $scot;
            $gamePoint->value = 300;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(1, count(GameNotification::getAllByUser($scot)));
            $this->assertEquals(300, $gamePoint->value);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(2, $gameLevel->value);

            //test user at general level 2 where they do have enough points to move up   (Game notification created)
            ZurmoConfigurationUtil::setByModuleName('ZurmoModule', 'gamificationModalNotificationsEnabled', true);
            $gamePoint = new GamePoint();
            $gamePoint->type = GamePoint::TYPE_USER_ADOPTION;
            $gamePoint->person = $scot;
            $gamePoint->value = 500;
            $this->assertTrue($gamePoint->save());
            Yii::app()->gameHelper->resolveLevelChange();
            $this->assertEquals(2, count(GameNotification::getAllByUser($scot)));
            $this->assertEquals(500, $gamePoint->value);
            $this->assertTrue($gameLevel->id > 0);
            $this->assertEquals(3, $gameLevel->value);
        }

        public function testResolveNewCollectionItems()
        {
            $bool = GameCollection::shouldReceiveCollectionItem();
            $this->assertTrue(is_bool($bool));
            Yii::app()->user->userModel      = User::getByUsername('super');
            $availableTypes = GameCollection::getAvailableTypes();
            $this->assertCount(31, $availableTypes);
            $collection     = GameCollection::resolveByTypeAndPerson($availableTypes[0], Yii::app()->user->userModel);
            $itemsData      = $collection->getItemsData();
            $randomItem      = array_rand($itemsData, 1);
            $compareData = array('Gate'        => 0,
                                 'Passport'    => 0,
                                 'Pilot'       => 0,
                                 'Luggage'     => 0,
                                 'TowTruck'    => 0);
            $this->assertTrue($randomItem == 'Gate' || $randomItem == 'Passport' ||
                              $randomItem == 'Pilot' || $randomItem == 'Luggage' || $randomItem == 'TowTruck');
            $compareData[$randomItem] = $compareData[$randomItem] + 1;
            $itemsData[$randomItem] = $itemsData[$randomItem] + 1;
            $collection->setItemsData($itemsData);
            $collection->save();
            $this->assertEquals($compareData, $collection->getItemsData());
        }
    }
?>
