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
     * Base class for displaying meetings on a calendar
     */
    abstract class UpcomingMeetingsCalendarView extends CalendarView
    {
        protected function makeDayEvents()
        {
            return $this->getDataProvider()->getData();
        }

        /**
         * @param null|string $stringTime
         * @return MeetingsCalendarDataProvider|void
         */
        protected function makeDataProvider($stringTime = null)
        {
            assert('is_string($stringTime) || $stringTime == null');
            return new MeetingsCalendarDataProvider('Meeting', $this->makeSearchAttributeData($stringTime));
        }

        /**
         * @param null|string $stringTime
         * @return array
         */
        protected function makeSearchAttributeData($stringTime = null)
        {
            assert('is_string($stringTime) || $stringTime == null');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'        => 'startDateTime',
                    'operatorType'         => 'greaterThan',
                    'value'                => DateTimeUtil::
                                              convertDateIntoTimeZoneAdjustedDateTimeBeginningOfDay(
                                              DateTimeUtil::getFirstDayOfAMonthDate($stringTime))
                ),
                2 => array(
                    'attributeName'        => 'startDateTime',
                    'operatorType'         => 'lessThan',
                    'value'                => DateTimeUtil::
                                              convertDateIntoTimeZoneAdjustedDateTimeEndOfDay(
                                              DateTimeUtil::getLastDayOfAMonthDate($stringTime))
                ),
                3 => array(
                    'attributeName'        => 'logged',
                    'operatorType'         => 'doesNotEqual',
                    'value'                => true
                ),
                4 => array(
                    'attributeName'        => 'logged',
                    'operatorType'         => 'isNull',
                    'value'                => null
                )
            );
            $searchAttributeData['structure'] = '(1 and 2 and (3 or 4))';
            return $searchAttributeData;
        }

        public static function getModuleClassName()
        {
            return 'MeetingsModule';
        }

        protected function getOnChangeMonthScript()
        {
            // Begin Not Coding Standard
            return "js:function(year, month, inst) {
                //Call to render new events
                $.ajax({
                    url      : $.param.querystring('" . $this->getPortletChangeMonthUrl() . "', '&month=' + month + '&year=' + year),
                    async    : false,
                    type     : 'GET',
                    dataType : 'html',
                    success  : function(data)
                    {
                        eval(data);
                        //Since the home page for some reason cannot render this properly in beforeShow, we are using a trick.
                        setTimeout('addSpansToDatesOnCalendar(\"' + inst.id + '\")', 100);
                    },
                    error : function()
                    {
                        //todo: error call
                    }
                });
            }";
            // End Not Coding Standard
        }

        protected function getOnSelectScript()
        {
            // Begin Not Coding Standard
            return "js:function(dateText, inst) {
                $.ajax({
                    url      : $.param.querystring('" . $this->getPortletSelectDayUrl() . "', '&stringTime=' + $('#calendarSelectedDate" . $this->uniqueLayoutId . "').val()),
                    async    : false,
                    type     : 'GET',
                    'beforeSend' : function(){
                                        jQuery('#modalContainer').html('');
                                        $(this).makeLargeLoadingSpinner(true, '#modalContainer');
                                        jQuery('#modalContainer').dialog({'title': dateText,
                                                                          'autoOpen':true,
                                                                          'modal': true,
                                                                          'height': 'auto',
                                                                          'position': 'center',
                                                                          'width':600}); return true;},
                    success  : function(data)
                    {
                        jQuery('#modalContainer').html(data)
                        //Since the home page for some reason cannot render this properly in beforeShow, we are using a trick.
                        setTimeout('addSpansToDatesOnCalendar(\"' + inst.id + '\")', 100);
                    },
                    error : function()
                    {
                        //todo: error call
                    }
                });
            }";
            // End Not Coding Standard
        }

        protected function getPortletChangeMonthUrl()
        {
            return Yii::app()->createUrl('/' . $this->resolvePortletModuleId() . '/defaultPortlet/viewAction',
                                                        array_merge($_GET, array(
                                                            'action'         => 'renderMonthEvents',
                                                            'portletId'      => $this->params['portletId'],
                                                            'uniqueLayoutId' => $this->uniqueLayoutId)));
        }

        protected function getPortletSelectDayUrl()
        {
            return Yii::app()->createUrl('/meetings/default/daysMeetingsFromCalendarModalList', $_GET);
        }

        /**
         * Called by ajax action when the calendar month is changed.  Needed to render additional events.
         */
        public function renderMonthEvents()
        {
            $month     = str_pad($_GET['month'], 2, '0', STR_PAD_LEFT);
            $year      = $_GET['year'];
            $dayEvents = $this->makeDataProvider($year . '-' . $month . '-01')->getData();
            foreach ($dayEvents as $event)
            {
                $dateTimestamp = DateTimeUtil::convertDbFormatDateTimeToTimestamp($event['dbDate']);
                $dateForJavascript = date('M j, Y', $dateTimestamp);
                echo "console.log('" . $dateForJavascript . "');calendarEvents[new Date('" . $dateForJavascript . "')] = new CalendarEvent('" . $event['label'] . "', '" . $event['className'] . "'); \n";
            }
        }

        /**
         * Override and implement in children classes
         */
        public function resolvePortletModuleId()
        {
            throw new NotImplementedException();
        }
    }
?>