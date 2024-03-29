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

    class MarketingList extends OwnedSecurableItem
    {
        public static function getByName($name)
        {
            return self::getByNameOrEquivalent('name', $name);
        }

        public static function getModuleClassName()
        {
            return 'MarketingListsModule';
        }

        public function __toString()
        {
            try
            {
                if (trim($this->name) == '')
                {
                    return Zurmo::t('Core', '(Unnamed)');
                }
                return $this->name;
            }
            catch (AccessDeniedSecurityException $e)
            {
                return '';
            }
        }

        protected static function getLabel($language = null)
        {
            return Zurmo::t('MarketingListsModule', 'Marketing List', array(), null, $language);
        }

        /**
         * Returns the display name for plural of the model class.
         * @return dynamic label name based on module.
         */
        protected static function getPluralLabel($language = null)
        {
            return Zurmo::t('MarketingListsModule', 'Marketing Lists', array(), null, $language);
        }

        public static function canSaveMetadata()
        {
            return true;
        }

        public static function isTypeDeletable()
        {
            return true;
        }

        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'name',
                    'description',
                    'fromName',
                    'fromAddress',
                    'anyoneCanSubscribe',
                ),
                'relations' => array(
                    'marketingListMembers'  => array(static::HAS_MANY,   'MarketingListMember', static::OWNED),
                    'autoresponders'        => array(static::HAS_MANY,   'Autoresponder', static::OWNED),
                    'campaigns'             => array(static::HAS_MANY,   'Campaign', static::OWNED),
                ),
                'rules' => array(
                    array('name',               'required'),
                    array('name',               'type',    'type' => 'string'),
                    array('name',               'length',  'min'  => 1, 'max' => 64),
                    array('description',        'type',    'type' => 'string'),
                    array('fromName',           'type', 'type' => 'string'),
                    array('fromName',           'length',  'min'  => 1, 'max' => 64),
                    array('fromAddress',        'type', 'type' => 'string'),
                    array('fromAddress',        'length',  'min'  => 6, 'max' => 64),
                    array('fromAddress',        'email'),
                    array('anyoneCanSubscribe', 'boolean'),
                    array('anyoneCanSubscribe', 'default', 'value' => false),
                ),
                'elements' => array(
                    'anyoneCanSubscribe' => 'CheckBox',
                    'description'        => 'TextArea',
                ),
                'defaultSortAttribute' => 'name',
            );
            return $metadata;
        }

        public static function hasReadPermissionsOptimization()
        {
            return true;
        }

        public static function getGamificationRulesType()
        {
            return 'MarketingListGamification';
        }

        public function addNewMember($contactId, $unsubscribed = false, $contact = null)
        {
            $member                     = new MarketingListMember();
            if (empty($contact))
            {
                $contact = Contact::getById($contactId);
            }
            $member->contact            = $contact;
            $member->unsubscribed       = $unsubscribed;
            $member->marketingList      = $this;
            if ($this->memberAlreadyExists($contact->id))
            {
                return false;
            }
            else
            {
                $saved = $member->unrestrictedSave();
                if ($saved)
                {
                    return true;
                }
                else
                {
                    throw new FailedToSaveModelException();
                }
            }
        }

        public function memberAlreadyExists($contactId)
        {
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'             => 'id',
                    'operatorType'              => 'equals',
                    'value'                     => $this->id,
                ),
                2 => array(
                    'attributeName'             => 'marketingListMembers',
                    'relatedModelData'          => array(
                        'attributeName'             => 'contact',
                        'relatedAttributeName'      => 'id',
                        'operatorType'              => 'equals',
                        'value'                     => $contactId
                    ),
                ),
            );
            $searchAttributeData['structure'] = '(1 and 2)';
            $joinTablesAdapter = new RedBeanModelJoinTablesQueryAdapter(get_class($this));
            $where             = RedBeanModelDataProvider::makeWhere(get_class($this), $searchAttributeData, $joinTablesAdapter);
            return self::getCount($joinTablesAdapter, $where, get_class($this), true);
        }

        public static function getByAnyoneCanSubscribe($anyoneCanSubscribe, $pageSize = null)
        {
            assert('is_int($anyoneCanSubscribe) || is_string($anyoneCanSubscribe)');
            assert('intval($anyoneCanSubscribe) == 0 || intval($anyoneCanSubscribe) == 1');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'             => 'anyoneCanSubscribe',
                    'operatorType'              => 'equals',
                    'value'                     => intval($anyoneCanSubscribe)
                ),
            );
            $searchAttributeData['structure'] = '1';
            $joinTablesAdapter                = new RedBeanModelJoinTablesQueryAdapter(get_called_class());
            $where = RedBeanModelDataProvider::makeWhere(get_called_class(), $searchAttributeData, $joinTablesAdapter);
            return self::getSubset($joinTablesAdapter, null, $pageSize, $where, null);
        }

        protected static function getIdsByAnyOneCanSubscribe($anyoneCanSubscribe, $pageSize = null)
        {
            assert('is_int($anyoneCanSubscribe) || is_string($anyoneCanSubscribe)');
            assert('$anyoneCanSubscribe == 0 || $anyoneCanSubscribe == 1');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'             => 'anyoneCanSubscribe',
                    'operatorType'              => 'equals',
                    'value'                     => intval($anyoneCanSubscribe)
                ),
            );
            $searchAttributeData['structure'] = '1';
            $joinTablesAdapter                = new RedBeanModelJoinTablesQueryAdapter(get_called_class());
            $where = RedBeanModelDataProvider::makeWhere(get_called_class(), $searchAttributeData, $joinTablesAdapter);
            return self::getSubsetIds($joinTablesAdapter, null, $pageSize, $where, null);
        }

        protected static function getIdsByUnsubscribed($contactId, $unsubscribed = 0, $pageSize = null)
        {
            assert('is_int($unsubscribed) || is_string($unsubscribed)');
            assert('intval($unsubscribed) == 0 || intval($unsubscribed) == 1');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'             => 'marketingListMembers',
                    'relatedModelData'          => array(
                        'attributeName'             => 'contact',
                        'relatedAttributeName'      => 'id',
                        'operatorType'              => 'equals',
                        'value'                     => intval($contactId)
                    ),
                ),
                2 => array(
                    'attributeName'             => 'marketingListMembers',
                    'relatedModelData'          => array(
                        'attributeName'             => 'unsubscribed',
                        'operatorType'              => 'equals',
                        'value'                     => intval($unsubscribed)
                    ),
                ),
            );
            $searchAttributeData['structure'] = '1 and 2';
            $joinTablesAdapter                = new RedBeanModelJoinTablesQueryAdapter(get_called_class());
            $where = RedBeanModelDataProvider::makeWhere(get_called_class(), $searchAttributeData, $joinTablesAdapter);
            return self::getSubsetIds($joinTablesAdapter, null, $pageSize, $where, null);
        }

        public static function getByUnsubscribedAndAnyoneCanSubscribe($contactId, $unsubscribed = 0,
                                                                      $anyoneCanSubscribe = 1, $pageSize = null)
        {
            // TODO: @Shoaibi: Critical: Add Tests to cover:
            $marketingListModels                = array();
            $subscribedMarketingListIds         = static::getIdsByUnsubscribed($contactId, $unsubscribed, $pageSize);
            $anyoneCanSubscribeMarketingListIds = static::getIdsByAnyOneCanSubscribe($anyoneCanSubscribe, $pageSize);
            $marketingListIds                   = CMap::mergeArray($subscribedMarketingListIds,
                                                                                $anyoneCanSubscribeMarketingListIds);
            $marketingListIds                   = array_unique($marketingListIds);
            foreach ($marketingListIds as $marketingListId)
            {
                $marketingListModelArray                = array();
                $marketingListModelArray['model']       = static::getById(intval($marketingListId));
                $marketingListModelArray['subscribed']  = false;
                if (in_array($marketingListId, $subscribedMarketingListIds))
                {
                    $marketingListModelArray['subscribed']  = true;
                }
                $marketingListModels[]                  = $marketingListModelArray;
            }
            return $marketingListModels;
        }
    }
?>