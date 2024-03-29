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
     * Sanitizer for handling task status.
     */
    class TaskStatusSanitizerUtil extends SanitizerUtil
    {
        /**
         * @param RedBean_OODBBean $rowBean
         */
        public function analyzeByRow(RedBean_OODBBean $rowBean)
        {
            if ($rowBean->{$this->columnName} != null)
            {
                $resolvedAcceptableValues = ArrayUtil::resolveArrayToLowerCase(static::getAcceptableValues());
                if (!in_array(strtolower($rowBean->{$this->columnName}), $resolvedAcceptableValues))
                {
                    $label = Zurmo::t('ImportModule',
                                      '{attributeLabel} specified is invalid and this row will be skipped during import.',
                                      array('{attributeLabel}' => Task::getAnAttributeLabel('status')));
                    $this->shouldSkipRow      = true;
                    $this->analysisMessages[] = $label;
                }
            }
        }

        /**
         * If a status value is missing or invalid, then skip the entire row during import.
         */
        public static function shouldNotSaveModelOnSanitizingValueFailure()
        {
            return true;
        }

        /**
         * Given a type, attempt to resolve it as a valid type.  If the type is invalid, a
         * InvalidValueToSanitizeException will be thrown.
         * @param mixed $value
         * @return sanitized value
         * @throws InvalidValueToSanitizeException
         */
        public function sanitizeValue($value)
        {
            if ($value == null)
            {
                return $value;
            }
            try
            {
                if (strtolower($value) == strtolower(Task::STATUS_NEW) ||
                    strtolower($value) == strtolower('New'))
                {
                    return Task::STATUS_NEW;
                }
                elseif (strtolower($value) == strtolower(Task::STATUS_IN_PROGRESS) ||
                        strtolower($value) == strtolower('In Progress'))
                {
                    return Task::STATUS_IN_PROGRESS;
                }
                elseif (strtolower($value) == strtolower(Task::STATUS_AWAITING_ACCEPTANCE) ||
                        strtolower($value) == strtolower('Awaiting Acceptance'))
                {
                    return Task::STATUS_AWAITING_ACCEPTANCE;
                }
                elseif (strtolower($value) == strtolower(Task::STATUS_REJECTED) ||
                        strtolower($value) == strtolower('Rejected'))
                {
                    return Task::STATUS_REJECTED;
                }
                elseif (strtolower($value) == strtolower(Task::STATUS_COMPLETED) ||
                        strtolower($value) == strtolower('Completed'))
                {
                    return Task::STATUS_COMPLETED;
                }
                else
                {
                    throw new InvalidValueToSanitizeException(Zurmo::t('ZurmoModule', 'Status specified is invalid.'));
                }
            }
            catch (NotFoundException $e)
            {
                throw new InvalidValueToSanitizeException(Zurmo::t('ZurmoModule', 'Status specified is invalid.'));
            }
        }

        protected static function getAcceptableValues()
        {
            return array(Task::STATUS_NEW,
                         Task::STATUS_IN_PROGRESS,
                         Task::STATUS_AWAITING_ACCEPTANCE,
                         Task::STATUS_REJECTED,
                         Task::STATUS_COMPLETED,
                         'New',
                         'In Progress',
                         'Awaiting Acceptance',
                         'Rejected',
                         'Completed');
        }
    }
?>