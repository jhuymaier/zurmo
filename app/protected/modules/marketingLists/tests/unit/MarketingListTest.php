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
    class MarketingListTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            SecurityTestHelper::createUsers();
        }

        public function setUp()
        {
            parent::setUp();
            Yii::app()->user->userModel = User::getByUsername('super');
        }

        public function testGetGamificationRulesType()
        {
            $this->assertEquals('MarketingListGamification', MarketingList::getGamificationRulesType());
        }

        public function testCreateAndGetMarketingListById()
        {
            $marketingList = new MarketingList();
            $marketingList->name        = 'Test Marketing List';
            $marketingList->description = 'Test Description';
            $marketingList->fromName    = 'Zurmo Sales';
            $marketingList->fromAddress = 'sales@zurmo.com';
            $this->assertTrue($marketingList->save());
            $id = $marketingList->id;
            unset($marketingList);
            $marketingList = MarketingList::getById($id);
            $this->assertEquals('Test Marketing List',  $marketingList->name);
            $this->assertEquals('Test Description',     $marketingList->description);
            $this->assertEquals('Zurmo Sales',          $marketingList->fromName);
            $this->assertEquals('sales@zurmo.com',      $marketingList->fromAddress);
        }

        public function testAtleastNameIsRequired()
        {
            $marketingList = new MarketingList();
            $this->assertFalse($marketingList->save());
            $errors = $marketingList->getErrors();
            $this->assertNotEmpty($errors);
            $this->assertCount(1, $errors);
            $this->assertEquals(array('name'), array_keys($errors));
        }

        /**
         * @depends testCreateAndGetMarketingListById
         */
        public function testGetMarketingListByName()
        {
            $marketingLists = MarketingList::getByName('Test Marketing List');
            $this->assertEquals(1, count($marketingLists));
            $this->assertEquals('Test Marketing List', $marketingLists[0]->name);
        }

        /**
         * @depends testCreateAndGetMarketingListById
         */
        public function testGetLabel()
        {
            $marketingLists = MarketingList::getByName('Test Marketing List');
            $this->assertEquals(1, count($marketingLists));
            $this->assertEquals('Marketing List',  $marketingLists[0]::getModelLabelByTypeAndLanguage('Singular'));
            $this->assertEquals('Marketing Lists', $marketingLists[0]::getModelLabelByTypeAndLanguage('Plural'));
        }

        public function testGetByOpenSubscription()
        {
            MarketingListTestHelper::createMarketingListByName('anyoneCanSubscribe', 'Some description',
                                                                                        'Zurmo', 'from@zurmo.com', 1);
            $anyoneCanSubscribeMarketingLists = MarketingList::getByAnyoneCanSubscribe(1);
            $this->assertNotEmpty($anyoneCanSubscribeMarketingLists);
            $this->assertCount(1, $anyoneCanSubscribeMarketingLists);
            $closeSubscriptionMarketingLists = MarketingList::getByAnyoneCanSubscribe(0);
            $this->assertNotEmpty($closeSubscriptionMarketingLists);
            $this->assertCount(1, $closeSubscriptionMarketingLists);
        }

        /**
         * @depends testCreateAndGetMarketingListById
         */
        public function testDeleteMarketingList()
        {
            $marketingLists = MarketingList::getAll();
            $this->assertEquals(2, count($marketingLists));
            $marketingLists[0]->delete();
            $marketingLists = MarketingList::getAll();
            $this->assertEquals(1, count($marketingLists));
        }
    }
?>