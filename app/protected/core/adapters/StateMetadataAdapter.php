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
     * Adapter class to manipulate state information
     * for a module into metadata.
     */
    abstract class StateMetadataAdapter implements StateMetadataAdapterInterface
    {
        protected $metadata;

        public static function getStateAttributeName()
        {
            throw new NotImplementedException();
        }

        public function __construct(array $metadata)
        {
            assert('isset($metadata["clauses"])');
            assert('isset($metadata["structure"])');
            $this->metadata = $metadata;
        }

        /**
         * Creates where clauses and adds structure information
         * to existing DataProvider metadata.
         */
        public function getAdaptedDataProviderMetadata()
        {
            $metadata = $this->metadata;
            $stateIds = $this->getStateIds();
            $clauseCount = count($metadata['clauses']);
            $startingCount = $clauseCount + 1;
            $structure = '';
            $first = true;
            //No StateIds mean the list should come up empty
            if (count($stateIds) == 0)
            {
                $metadata['clauses'][$startingCount] = array(
                    'attributeName' => 'state',
                    'operatorType'  => 'equals',
                    'value'         => -1
                );
                $structure .= $startingCount;
                $startingCount++;
            }
            else
            {
                foreach ($stateIds as $stateId)
                {
                    $metadata['clauses'][$startingCount] = array(
                        'attributeName' => 'state',
                        'operatorType'  => 'equals',
                        'value'         => $stateId
                    );
                    if (!$first)
                    {
                        $structure .= ' or ';
                    }
                    $first = false;
                    $structure .= $startingCount;
                    $startingCount++;
                }
            }
            if (empty($metadata['structure']))
            {
                $metadata['structure'] = '(' . $structure . ')';
            }
            else
            {
                $metadata['structure'] = '(' . $metadata['structure'] . ') and (' . $structure . ')';
            }
            return $metadata;
        }

        /**
         * @return array of states that should be included
         * for the Module
         */
        abstract protected function getStateIds();

        /**
         * Override method in extended class to implement.
         * @return string of state model class name.
         */
        public static function getStateModelClassName()
        {
            throw new NotImplementedException();
        }

        /**
         * Override as needed.
         * @param RedBeanModel $model
         * @return null
         */
        public static function getModuleClassNameByModel(RedBeanModel $model)
        {
            return $model::getModuleClassName();
        }

        public static function resolveModuleClassNameByModel(RedBeanModel $model)

        {
            $moduleClassName   = $model::getModuleClassName();
            $stateMetadataAdapterClassName = $moduleClassName::getStateMetadataAdapterClassName();
            if ($stateMetadataAdapterClassName != null)
            {
                $moduleClassName = $stateMetadataAdapterClassName::getModuleClassNameByModel($model);
            }
            return $moduleClassName;
        }
    }
?>