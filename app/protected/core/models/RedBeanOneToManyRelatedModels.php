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

    $basePath = Yii::app()->getBasePath();
    require_once("$basePath/../../redbean/rb.php");

    /**
     * Relates models as RedBean links, so that
     * the relationship is 1:M via a foreign key.
     */
    class RedBeanOneToManyRelatedModels extends RedBeanMutableRelatedModels
    {
        protected $relatedModelClassName;

        protected $owns;

        /**
         * @see RedBeanModel::LINK_TYPE_ASSUMPTIVE
         * @see RedBeanModel::LINK_TYPE_SPECIFIC
         * @see RedBeanModel::LINK_TYPE_POLYMORPHIC
         * @var integer
         */
        protected $linkType;

        protected $linkName;

        /**
         * Constructs a new RedBeanOneToManyRelatedModels. The models are retrieved lazily.
         * RedBeanOneToManyRelatedModels are only constructed with beans by the model.
         * Beans are never used by the application directly.
         */
        public function __construct(RedBean_OODBBean $bean, $modelClassName, $relatedModelClassName,
                                    $owns, $linkType, $linkName = null)
        {
            assert('is_string($modelClassName)');
            assert('is_string($relatedModelClassName)');
            assert('$modelClassName        != ""');
            assert('$relatedModelClassName != ""');
            assert('is_bool($owns)');
            assert('is_int($linkType)');
            assert('is_string($linkName) || $linkName == null');
            assert('($linkType == RedBeanModel::LINK_TYPE_ASSUMPTIVE && $linkName == null) ||
                    ($linkType != RedBeanModel::LINK_TYPE_ASSUMPTIVE && $linkName != null)');
            $this->rewind();
            $this->modelClassName        = $modelClassName;
            $this->relatedModelClassName = $relatedModelClassName;
            $this->owns                  = $owns;
            $this->linkType              = $linkType;
            $this->linkName              = $linkName;
            $this->constructRelatedBeansAndModels($modelClassName, $bean);
        }

        public function getModelClassName()
        {
            return $this->modelClassName;
        }

        /**
         * Handles constructing the relatedBeansAndModels with special attention to the case where it is PolyOneToMany
         * @param string $modelClassName
         * @param mixed $sqlOrBean
         */
        private function constructRelatedBeansAndModels($modelClassName, $sqlOrBean = '')
        {
            assert('is_string($sqlOrBean) || $sqlOrBean instanceof RedBean_OODBBean');
            $tableName = RedBeanModel::getTableName($modelClassName);
            if (is_string($sqlOrBean))
            {
                $this->relatedBeansAndModels = array_values($beans = ZurmoRedBean::find($tableName, $sqlOrBean));
            }
            else
            {
                assert('$sqlOrBean instanceof RedBean_OODBBean');
                $this->bean = $sqlOrBean;
                try
                {
                    if ($this->bean->id > 0)
                    {
                        if ($this->linkType == RedBeanModel::LINK_TYPE_POLYMORPHIC)
                        {
                            $value           = array();
                            $values['id']    = $this->bean->id;
                            $values['type']  = $this->bean->getMeta('type');

                            $this->relatedBeansAndModels = array_values(ZurmoRedBean::find( $tableName,
                                                                    strtolower($this->linkName) . '_id = :id AND ' .
                                                                    strtolower($this->linkName) . '_type = :type',
                                                                    $values));
                        }
                        else
                        {
                            $relatedIds                  = ZurmoRedBeanLinkManager::getKeys($this->bean, $tableName,
                                                                                            $this->resolveLinkNameForCasing());
                            $this->relatedBeansAndModels = array_values(ZurmoRedBean::batch($tableName, $relatedIds));
                        }
                    }
                    else
                    {
                        $this->relatedBeansAndModels = array();
                    }
                }
                catch (RedBean_Exception_SQL $e)
                {
                    // SQLSTATE[42S02]: Base table or view not found...
                    // SQLSTATE[42S22]: Column not found...
                    if (!in_array($e->getSQLState(), array('42S02', '42S22')))
                    {
                        throw $e;
                    }
                    $this->relatedBeansAndModels = array();
                }
            }
        }

        public function save($runValidation = true)
        {
            if (!parent::save($runValidation))
            {
                return false;
            }
            foreach ($this->deferredRelateBeans as $bean)
            {
                if ($this->linkType == RedBeanModel::LINK_TYPE_POLYMORPHIC)
                {
                    if ($this->bean->id == null)
                    {
                        ZurmoRedBean::store($this->bean);
                    }
                    $polyIdFieldName          = strtolower($this->linkName) . '_id';
                    $polyTypeFieldName        = strtolower($this->linkName) . '_type';
                    $bean->$polyTypeFieldName = $this->bean->getMeta('type');
                    $bean->$polyIdFieldName   = $this->bean->id;
                }
                else
                {
                    ZurmoRedBeanLinkManager::link($bean, $this->bean, $this->resolveLinkNameForCasing());
                }
                ZurmoRedBean::store($bean);
            }
            $this->deferredRelateBeans = array();
            $tableName = RedBeanModel::getTableName($this->relatedModelClassName);
            foreach ($this->deferredUnrelateBeans as $bean)
            {
                if (!$this->owns)
                {
                    if ($this->linkType == RedBeanModel::LINK_TYPE_POLYMORPHIC)
                    {
                        throw new NotSupportedException("Polymorphic relations can not be NOT_OWNED");
                    }
                    ZurmoRedBeanLinkManager::breakLink($bean, $tableName, $this->resolveLinkNameForCasing());
                    ZurmoRedBean::store($bean);
                }
                else
                {
                    ZurmoRedBean::trash($bean);
                }
            }
            $this->deferredUnrelateBeans = array();
            return true;
        }

        protected function resolveLinkNameForCasing()
        {
            if ($this->linkName != null)
            {
                return strtolower($this->linkName);
            }
        }

        /**
         * Return an array of stringified values for each of the contained models.
         */
        public function getStringifiedData()
        {
            $data = null;
            foreach ($this as $containedModel)
            {
                if ($containedModel->id > 0)
                {
                    $data[] = strval($containedModel);
                }
            }
            return $data;
        }
    }
?>
