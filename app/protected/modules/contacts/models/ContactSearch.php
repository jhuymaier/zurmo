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

    class ContactSearch extends BaseModelAutoCompleteUtil
    {
        /**
         * For a give Contact name, run a partial search by
         * full name and retrieve contact models.
         * @param string $partialName
         * @param int $pageSize
         * @param null|string $stateMetadataAdapterClassName
         * @param $autoCompleteOptions
         */
        public static function getContactsByPartialFullName($partialName, $pageSize,
                                                    $stateMetadataAdapterClassName = null, $autoCompleteOptions = null)
        {
            assert('is_string($partialName)');
            assert('is_int($pageSize)');
            assert('$stateMetadataAdapterClassName == null || is_string($stateMetadataAdapterClassName)');
            $personTableName   = RedBeanModel::getTableName('Person');
            $joinTablesAdapter = new RedBeanModelJoinTablesQueryAdapter('Contact');
            if (!$joinTablesAdapter->isTableInFromTables('person'))
            {
                $joinTablesAdapter->addFromTableAndGetAliasName($personTableName, "{$personTableName}_id");
            }
            $metadata = array('clauses' => array(), 'structure' => '');
            if ($stateMetadataAdapterClassName != null)
            {
                $stateMetadataAdapter = new $stateMetadataAdapterClassName($metadata);
                $metadata = $stateMetadataAdapter->getAdaptedDataProviderMetadata();
                $metadata['structure'] = '(' . $metadata['structure'] . ')';
            }
            $where  = RedBeanModelDataProvider::makeWhere('Contact', $metadata, $joinTablesAdapter);
            if ($where != null)
            {
                $where .= 'and';
            }
            $where .= self::getWherePartForPartialNameSearchByPartialName($partialName);
            static::handleAutoCompleteOptions($joinTablesAdapter, $where, $autoCompleteOptions);
            return Contact::getSubset($joinTablesAdapter, null, $pageSize, $where, "person.firstname, person.lastname");
        }

        /**
         * For a give Contact name or email address, run a partial search by
         * full name and email address and retrieve contact models.
         * @param string $partialNameOrEmailAddress
         * @param int $pageSize
         * @param null|string $stateMetadataAdapterClassName
         * @param null|string $operatorType
         * @param $autoCompleteOptions
         */
        public static function getContactsByPartialFullNameOrAnyEmailAddress($partialNameOrEmailAddress, $pageSize,
                                                                             $stateMetadataAdapterClassName = null,
                                                                             $operatorType = null,
                                                                             $autoCompleteOptions = null)
        {
            assert('is_string($partialNameOrEmailAddress)');
            assert('is_int($pageSize)');
            assert('$stateMetadataAdapterClassName == null || is_string($stateMetadataAdapterClassName)');
            assert('$operatorType == null || is_string($operatorType)');
            if ($operatorType == null)
            {
              $operatorType = 'startsWith';
            }
            $metadata = array();
            $metadata['clauses'] = array(
                1 => array(
                    'attributeName'        => 'primaryEmail',
                    'relatedAttributeName' => 'emailAddress',
                    'operatorType'         => $operatorType,
                    'value'                => $partialNameOrEmailAddress,
                ),
                2 => array(
                    'attributeName'        => 'secondaryEmail',
                    'relatedAttributeName' => 'emailAddress',
                    'operatorType'         => $operatorType,
                    'value'                => $partialNameOrEmailAddress,
                ),
            );
            $metadata['structure'] = '((1 or 2) or partialnamesearch)';
            $joinTablesAdapter   = new RedBeanModelJoinTablesQueryAdapter('Contact');
            if ($stateMetadataAdapterClassName != null)
            {
                $stateMetadataAdapter = new $stateMetadataAdapterClassName($metadata);
                $metadata = $stateMetadataAdapter->getAdaptedDataProviderMetadata();
            }
            $where  = RedBeanModelDataProvider::makeWhere('Contact', $metadata, $joinTablesAdapter);
            $partialNameWherePart = self::getWherePartForPartialNameSearchByPartialName($partialNameOrEmailAddress);
            $where  = strtr(strtolower($where), array('partialnamesearch' => $partialNameWherePart));
            static::handleAutoCompleteOptions($joinTablesAdapter, $where, $autoCompleteOptions);
            return Contact::getSubset($joinTablesAdapter, null, $pageSize, $where, "person.firstname, person.lastname");
        }

        protected static function getWherePartForPartialNameSearchByPartialName($partialName)
        {
            assert('is_string($partialName)');
            $fullNameSql = DatabaseCompatibilityUtil::concat(array('person.firstname',
                                                                   '\' \'',
                                                                   'person.lastname'));
            return "      (person.firstname      like '$partialName%' or "    .
                   "       person.lastname       like '$partialName%' or "    .
                   "       $fullNameSql like '$partialName%') ";
        }

        /**
         * For a given email address, run search by email address and retrieve contact models.
         * @param string $emailAddress
         * @param null|int $pageSize
         * @param null|sting $stateMetadataAdapterClassName
         * @param $autoCompleteOptions
         */
        public static function getContactsByAnyEmailAddress($emailAddress, $pageSize = null,
                                                $stateMetadataAdapterClassName = null, $autoCompleteOptions = null)
        {
            assert('is_string($emailAddress)');
            $metadata = array();
            $metadata['clauses'] = array(
                1 => array(
                    'attributeName'        => 'primaryEmail',
                    'relatedAttributeName' => 'emailAddress',
                    'operatorType'         => 'equals',
                    'value'                => $emailAddress,
                ),
                2 => array(
                    'attributeName'        => 'secondaryEmail',
                    'relatedAttributeName' => 'emailAddress',
                    'operatorType'         => 'equals',
                    'value'                => $emailAddress,
                ),
            );
            $metadata['structure'] = '(1 or 2)';
            $joinTablesAdapter   = new RedBeanModelJoinTablesQueryAdapter('Contact');
            if ($stateMetadataAdapterClassName != null)
            {
                $stateMetadataAdapter = new $stateMetadataAdapterClassName($metadata);
                $metadata = $stateMetadataAdapter->getAdaptedDataProviderMetadata();
            }
            $where  = RedBeanModelDataProvider::makeWhere('Contact', $metadata, $joinTablesAdapter);
            static::handleAutoCompleteOptions($joinTablesAdapter, $where, $autoCompleteOptions);
            return Contact::getSubset($joinTablesAdapter, null, $pageSize, $where);
        }
    }
?>
