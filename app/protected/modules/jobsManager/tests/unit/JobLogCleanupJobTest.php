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

    class JobLogCleanupJobTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
        }

        public function testRun()
        {
            //Create 2 jobLogs, and set one with a date over a week ago (8 days ago) for the endDateTime
            $eightDaysAgoTimestamp = DateTimeUtil::convertTimestampToDbFormatDateTime(time() - (60 * 60 *24 * 8));
            $jobLog                = new JobLog();
            $jobLog->type          = 'Monitor';
            $jobLog->startDateTime = $eightDaysAgoTimestamp;
            $jobLog->endDateTime   = $eightDaysAgoTimestamp;
            $jobLog->status        = JobLog::STATUS_COMPLETE_WITHOUT_ERROR;
            $jobLog->isProcessed = false;
            $jobLog->save();

            $jobLog2                = new JobLog();
            $jobLog2->type          = 'ImportCleanup';
            $jobLog2->startDateTime = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
            $jobLog2->endDateTime   = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
            $jobLog2->status        = JobLog::STATUS_COMPLETE_WITHOUT_ERROR;
            $jobLog2->isProcessed = false;
            $jobLog2->save();

            $sql = 'select count(*) count from item';
            $row = ZurmoRedBean::getRow($sql);
            $this->assertEquals(4, $row['count']);

            $job = new JobLogCleanupJob();
            $this->assertTrue($job->run());
            $jobLogs = JobLog::getAll();
            $this->assertEquals(1, count($jobLogs));
            $this->assertEquals($jobLog2->id, $jobLogs[0]->id);
            $sql = 'select count(*) count from item';
            $row = ZurmoRedBean::getRow($sql);
            $this->assertEquals(3, $row['count']);
        }
    }
?>