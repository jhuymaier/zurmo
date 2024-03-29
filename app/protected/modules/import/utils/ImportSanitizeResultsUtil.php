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
     * Helper class for working resulting messages from sanitizing a values in a row.
     */
    class ImportSanitizeResultsUtil
    {
        /**
         * Messages generated through sanitizing the row data.
         * @var unknown_type
         */
        private $messages = array();

        /**
         * Messages generated when creating or updating a related model during row data sanitization
         * @var array
         */
        private $relatedModelMessages = array();

        /**
         * Some sanitization routines, if they run into an error, means the entire row should be skipped for
         * making or updating a model.  Some sanitization does not require the entire row to be skipped, just the value.
         * If the row is required to be skipped, this value should be set to false @see setModelShouldNotBeSaved()
         * @var boolean
         */
        private $saveModel = true;

        /**
         * @see $saveModel
         */
        public function setModelShouldNotBeSaved()
        {
            $this->saveModel = false;
        }

        /**
         * Given a message, add it to the messages collection.
         * @param string $message
         */
        public function addMessage($message)
        {
            assert('is_string($message)');
            $this->messages[] = $message;
        }

        /**
         * Given a message, add it to the related model messages collection.
         * @param string $message
         */
        public function addRelatedModelMessage($message)
        {
            assert('is_string($message)');
            $this->relatedModelMessages[] = $message;
        }

        /**
         * @return An array of messages.
         */
        public function getMessages()
        {
            return $this->messages;
        }

        /**
         * @return true/false if the model should be saved or skipped.
         */
        public function shouldSaveModel()
        {
            return $this->saveModel;
        }

        public function getRelatedModelMessages()
        {
            return $this->relatedModelMessages;
        }
    }
?>