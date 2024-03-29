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
     * Application to be used during unit testing
     */
    class WebTestApplication extends WebApplication
    {
        private $configLanguageValue;
        private $configTimeZoneValue;

        /**
         * Override for walkthrough tests. Need to store the config data so certain values can
         *  be reset to the original config value when resetting the application to run another walkthrough.
         */
        public function __construct($config = null)
        {
            parent::__construct($config);
            $this->configLanguageValue = $this->language;
            $this->configTimeZoneValue = $this->timeZoneHelper->getTimeZone();

            // We need explicitly to raise this event, because CApplication::run() method
            // where OnBeginRequest event is raised is never called
            // For more informationn check: app/protected/tests/common/bootstrap.php
            if ($this->hasEventHandler('onBeginRequest'))
            {
                $this->onBeginRequest(new CEvent($this));
            }
        }

        /**
         * Override because when testing, we always want to raise the event
         * instead of only raising it once.  This is because using phpunit and
         * unit tests, it is possible we will have the application execute ->end
         * multiple times during testing.
         * Raised right AFTER the application processes the request.
         * @param CEvent $event the event parameter
         */
        public function onEndRequest($event)
        {
            $this->raiseEvent('onEndRequest', $event);
        }

        public function getConfigLanguageValue()
        {
            return $this->configLanguageValue;
        }

        public function getConfigTimeZoneValue()
        {
            return $this->configTimeZoneValue;
        }
    }
?>
