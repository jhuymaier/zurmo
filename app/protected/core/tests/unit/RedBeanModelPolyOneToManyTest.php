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
     * Test class for polymorphic relationships. An example is files. You can have files related to a single item, could
     * be a note or a comment or conversation. But on the file itself, there are 2 columns, one to describe this relationship
     * and the other for the linking id.
     */
    class RedBeanModelPolyOneToManyTest extends BaseTest
    {
        public static function getDependentTestModelClassNames()
        {
            return array('TestPolyOneToManyOneSide',
                            'TestPolyOneToManyOneSideTwo',
                            'TestPolyOneToManyPolySide',
                            'TestPolyOneToManyPolySideOwned'
                        );
        }

        public function testPolyOneToManyNotOwned()
        {
            $polySide = new TestPolyOneToManyPolySide();
            $polySide->name = 'polySideTest';

            $oneSide = new TestPolyOneToManyOneSide();
            $oneSide->name  = 'oneSideTest';
            $oneSide->polys->add($polySide);
            $this->assertTrue($oneSide->save());

            $polySideId = $polySide->id;
            $this->assertTrue($polySideId > 0);

            $oneSideId = $oneSide->id;
            $oneSide->forget();
            unset($oneSide);

            $polySide2 = new TestPolyOneToManyPolySide();
            $polySide2->name = 'polySideTest2';

            $oneSide2 = new TestPolyOneToManyOneSideTwo();
            $oneSide2->name  = 'oneSideTwoTest';
            $oneSide2->polysTwo->add($polySide2);
            $this->assertTrue($oneSide2->save());

            $polySide2Id = $polySide2->id;
            $this->assertTrue($polySide2Id > 0);

            $oneSide2Id = $oneSide2->id;
            $oneSide2->forget();
            unset($oneSide2);

            //Get oneSide and make sure it has one polySide that matches the appropriate id
            $oneSide = TestPolyOneToManyOneSide::getById($oneSideId);
            $this->assertEquals(1, $oneSide->polys->count());
            $this->assertEquals($polySideId, $oneSide->polys[0]->id);

            //Get oneSide2 and make sure it has one polySide2 that matches the appropriate id
            $oneSide2 = TestPolyOneToManyOneSideTwo::getById($oneSide2Id);
            $this->assertEquals(1, $oneSide2->polysTwo->count());
            $this->assertEquals($polySide2Id, $oneSide2->polysTwo[0]->id);

            //do a direct sql to get the row for polySide
            $row = ZurmoRedBean::getRow('select * from testpolyonetomanypolyside');
            $this->assertTrue(!isset($row['testpolyonetomanyoneside_id']));
            $this->assertTrue(!isset($row['testpolyonetomanyonesidetwo_id']));
            //Confirm the poly type and poly id columns are there.
            $this->assertTrue(isset($row['polytest_type']));
            $this->assertTrue(isset($row['polytest_id']));

            //test adding an extra PolySide to oneSide
            $polySide3 = new TestPolyOneToManyPolySide();
            $polySide3->name = 'polySideTest3';
            $oneSide->polys->add($polySide3);
            $this->assertTrue($oneSide->save());
            $polySide3Id = $polySide3->id;
            $oneSide->forget();
            unset($oneSide);

            //Now test there are 2 related polys
            $oneSide = TestPolyOneToManyOneSide::getById($oneSideId);
            $this->assertEquals(2, $oneSide->polys->count());
            $this->assertEquals($polySideId, $oneSide->polys[0]->id);
            $this->assertEquals($polySide3Id, $oneSide->polys[1]->id);

            //test disconnect a polySide, it should also remove the attached model from db. TestPolyOneToManyPolySide should be three
            $polySide = $oneSide->polys[0];
            $oneSide->polys->remove($polySide);
            $this->assertTrue($oneSide->save());
            $this->assertEquals(2, TestPolyOneToManyPolySide::getCount());

            //Now test there is 1 related polys
            $oneSide = TestPolyOneToManyOneSide::getById($oneSideId);
            $this->assertEquals(1, $oneSide->polys->count());
            $this->assertEquals($polySide3Id, $oneSide->polys[0]->id);

            $this->assertEquals(2, TestPolyOneToManyPolySide::getCount());
            $this->assertTrue($oneSide->delete());
            $this->assertEquals(1, TestPolyOneToManyPolySide::getCount());
            TestPolyOneToManyPolySide::deleteAll();
            $this->assertEquals(0, TestPolyOneToManyPolySide::getCount());
        }

        /**
         * @depends testPolyOneToManyNotOwned
         */
        public function testPolyOneToManyOwned()
        {
            $this->assertEquals(0, count(TestPolyOneToManyPolySide::getAll()));

            $polySide = new TestPolyOneToManyPolySideOwned();
            $polySide->name = 'polySideTest';

            $oneSide = new TestPolyOneToManyOneSide();
            $oneSide->name  = 'oneSideTest';
            $oneSide->ownedPolys->add($polySide);
            $this->assertTrue($oneSide->save());

            $polySideId = $polySide->id;
            $this->assertTrue($polySideId > 0);

            $oneSideId = $oneSide->id;
            $oneSide->forget();
            unset($oneSide);

            $polySide2 = new TestPolyOneToManyPolySideOwned();
            $polySide2->name = 'polySideTest2';

            $oneSide2 = new TestPolyOneToManyOneSideTwo();
            $oneSide2->name  = 'oneSideTwoTest';
            $oneSide2->ownedPolysTwo->add($polySide2);
            $this->assertTrue($oneSide2->save());

            $polySide2Id = $polySide2->id;
            $this->assertTrue($polySide2Id > 0);

            $oneSide2Id = $oneSide2->id;
            $oneSide2->forget();
            unset($oneSide2);

            $this->assertEquals(0, count(TestPolyOneToManyPolySide::getAll()));
            $this->assertEquals(2, count(TestPolyOneToManyPolySideOwned::getAll()));

            //Get oneSide and make sure it has one polySide that matches the appropriate id
            $oneSide = TestPolyOneToManyOneSide::getById($oneSideId);
            $this->assertEquals(1, $oneSide->ownedPolys->count());
            $this->assertEquals($polySideId, $oneSide->ownedPolys[0]->id);

            //Get oneSide2 and make sure it has one polySide2 that matches the appropriate id
            $oneSide2 = TestPolyOneToManyOneSideTwo::getById($oneSide2Id);
            $this->assertEquals(1, $oneSide2->ownedPolysTwo->count());
            $this->assertEquals($polySide2Id, $oneSide2->ownedPolysTwo[0]->id);

            $this->assertTrue($oneSide->delete());
            $this->assertEquals(1, count(TestPolyOneToManyPolySideOwned::getAll()));
            $this->assertTrue($oneSide2->delete());
            $this->assertEquals(0, count(TestPolyOneToManyPolySideOwned::getAll()));
        }
    }
?>
