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
     * Model for game notification data
     */
    class GameNotification extends RedBeanModel
    {
        const TYPE_LEVEL_CHANGE       = 'LevelChange';

        const TYPE_NEW_BADGE          = 'NewBadge';

        const TYPE_BADGE_GRADE_CHANGE = 'GradeChange';

        public function __toString()
        {
            return Zurmo::t('Core', '(Unnamed)');
        }

        /**
         * Given a user, retrieval all notifications for that user, sorted desc by Id
         * @param User $user
         * @return array $models
         */
        public static function getAllByUser(User $user)
        {
            assert('$user->id > 0');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'        => 'user',
                    'relatedAttributeName' => 'id',
                    'operatorType'         => 'equals',
                    'value'                => $user->id,
                ),
            );
            $searchAttributeData['structure'] = '1';
            $joinTablesAdapter = new RedBeanModelJoinTablesQueryAdapter('GameNotification');
            $where  = RedBeanModelDataProvider::makeWhere('GameNotification', $searchAttributeData, $joinTablesAdapter);
            $models = self::getSubset($joinTablesAdapter, null, null, $where, 'gamenotification.id asc');
            return $models;
        }

        public static function getModuleClassName()
        {
            return 'GamificationModule';
        }

        public static function canSaveMetadata()
        {
            return false;
        }

        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'serializedData',
                ),
                'relations' => array(
                    'user' => array(static::HAS_ONE, 'User'),
                ),
                'rules' => array(
                    array('user',           'required'),
                    array('serializedData', 'required'),
                    array('serializedData', 'type', 'type' => 'string'),
                ),
            );
            return $metadata;
        }

        public static function isTypeDeletable()
        {
            return true;
        }

        /**
         * Sets the serializedData for a notification when a new level is reached
         * @param integer $nextLevelValue
         * @param integer $coinsValue
         */
        public function setLevelChangeByNextLevelValue($nextLevelValue, $coinsValue)
        {
            assert('is_int($nextLevelValue)');
            assert('is_int($coinsValue)');
            $data = array('type' => self::TYPE_LEVEL_CHANGE, 'levelValue' => $nextLevelValue);
            if ($coinsValue > 0)
            {
                $data['coins'] = $coinsValue;
            }
            $this->serializedData = serialize($data);
        }

        /**
         * Sets the serializedData for a notification when a new badge is received.
         * @param string $badgeType
         */
        public function setNewBadgeByType($badgeType)
        {
            assert('is_string($badgeType)');
            $this->serializedData = serialize(array('type' => self::TYPE_NEW_BADGE, 'badgeType' => $badgeType));
        }

        /**
         * Sets the serializedData for a notification when a badge grade changes.
         * @param string $badgeType
         * @param integer $newGrade
         */
        public function setBadgeGradeChangeByTypeAndNewGrade($badgeType, $newGrade)
        {
            assert('is_string($badgeType)');
            assert('is_int($newGrade)');
            $this->serializedData = serialize(array('type'      => self::TYPE_BADGE_GRADE_CHANGE,
                                                    'badgeType' => $badgeType,
                                                    'grade'     => $newGrade));
        }

        public function getUnserializedData()
        {
            return unserialize($this->serializedData);
        }

        /**
         * Returns the display name for the model class.
         * @param null | string $language
         * @return dynamic label name based on module.
         */
        protected static function getLabel($language = null)
        {
            return Zurmo::t('GamificationModule', 'Game Notification', array(), null, $language);
        }

        /**
         * Returns the display name for plural of the model class.
         * @param null | string $language
         * @return dynamic label name based on module.
         */
        protected static function getPluralLabel($language = null)
        {
            return Zurmo::t('GamificationModule', 'Game Notifications', array(), null, $language);
        }
    }
?>