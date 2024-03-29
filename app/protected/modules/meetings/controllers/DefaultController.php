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

    class MeetingsDefaultController extends ActivityModelsDefaultController
    {
        public function actionDaysMeetingsFromCalendarModalList($stringTime, $redirectUrl)
        {
            if (isset($_GET['ownerOnly']))
            {
                $ownerOnly = true;
            }
            else
            {
                $ownerOnly = false;
            }
            if (isset($_GET['relationModelId']))
            {
                $relationModelClassName = $_GET['relationModelClassName'];
                $relationModel          = $relationModelClassName::getById((int)$_GET['relationModelId']);
            }
            else
            {
                $relationModel = null;
            }
            if (isset($_GET['relationModuleId']))
            {
                $relationModuleId = $_GET['relationModuleId'];
            }
            else
            {
                $relationModuleId = null;
            }
            Yii::app()->getClientScript()->setToAjaxMode();
            $meetingsView = $this->resolveViewForActionDaysMeetingsFromCalendarModalList($stringTime, $redirectUrl,
                                                                                $ownerOnly, $relationModel, $relationModuleId);
            $view = new ModalView($this, $meetingsView);
            echo $view->render();
        }

        protected function resolveViewForActionDaysMeetingsFromCalendarModalList($stringTime, $redirectUrl,
                                                                                $ownerOnly, $relationModel, $relationModuleId)
        {
            $meetingsView = new DaysMeetingsFromCalendarModalListView(
                $this->getId(),
                $this->getModule()->getId(),
                $stringTime,
                $redirectUrl,
                $ownerOnly,
                $relationModel,
                $relationModuleId
            );

            $dataProvider = $meetingsView->getDataProvider();
            if ($dataProvider->getItemCount() > 0)
            {
                return $meetingsView;
            }
            else
            {
                return new NoMeetingsYetView($redirectUrl, $this->getId(),
                                        $this->getModule()->getId(), $relationModel, $relationModuleId, $stringTime);
            }
        }

        public function actionCreateMeeting($redirectUrl, $startDate = null)
        {
            assert('$startDate == null || is_string($startDate)');
            $meeting = new Meeting();
            if ($startDate != null)
            {
                $meeting->startDateTime = DateTimeUtil::convertDateIntoTimeZoneAdjustedDateTimeBeginningOfDay($startDate);
            }
            $this->actionCreateByModel($meeting, $redirectUrl);
        }

        public function actionCreateFromRelationAndStartDate($relationAttributeName, $relationModelId,
                                                            $relationModuleId, $redirectUrl, $startDate)
        {
            $modelClassName   = $this->getModule()->getPrimaryModelName();
            $meeting          = $this->resolveNewModelByRelationInformation( new $modelClassName(),
                                                                                $relationAttributeName,
                                                                                (int)$relationModelId,
                                                                                $relationModuleId);
            if ($startDate != null)
            {
                $meeting->startDateTime = DateTimeUtil::convertDateIntoTimeZoneAdjustedDateTimeBeginningOfDay($startDate);
            }
            $this->actionCreateByModel($meeting, $redirectUrl);
        }
    }
?>
