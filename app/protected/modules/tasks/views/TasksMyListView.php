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
     * Class used for the dashboard, selectable by users to display a list of their tasks or filtered any way.
     */
    class TasksMyListView extends SecuredMyListView
    {
        /**
         * @return string
         */
        protected function getSortAttributeForDataProvider()
        {
            return 'dueDateTime';
        }

        /**
         * @return array
         */
        public static function getDefaultMetadata()
        {
            $metadata = array(
                'perUser' => array(
                    'title' => "eval:Zurmo::t('TasksModule', 'My Open TasksModulePluralLabel', LabelUtil::getTranslationParamsForAllModules())",
                    'searchAttributes' => array('ownedItemsOnly' => true, 'completed' => false),
                ),
                'global' => array(
                    'derivedAttributeTypes' => array(
                        'CloseTaskCheckBox',
                    ),
                    'nonPlaceableAttributeNames' => array(
                        'latestDateTime',
                    ),
                    'gridViewType' => RelatedListView::GRID_VIEW_TYPE_STACKED,
                    'panels' => array(
                        array(
                            'rows' => array(
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'null', 'type' => 'CloseTaskCheckBox'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'name', 'type' => 'Text', 'isLink' => true),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'dueDateTime', 'type' => 'DateTime'),
                                            ),
                                        ),
                                    )
                                ),
                            ),
                        ),
                    ),
                ),
            );
            return $metadata;
        }

        /**
         * @return string
         */
        public static function getModuleClassName()
        {
            return 'TasksModule';
        }

        /**
         * @return string
         */
        public static function getDisplayDescription()
        {
            return Zurmo::t('TasksModule', 'My Open TasksModulePluralLabel', LabelUtil::getTranslationParamsForAllModules());
        }

        /**
         * @return TasksSearchForm
         */
        protected function getSearchModel()
        {
            $modelClassName = $this->modelClassName;
            $model = new $modelClassName(false);
            return new TasksSearchForm($model);
        }

        /**
         * @return string
         */
        protected static function getConfigViewClassName()
        {
            return 'TasksMyListConfigView';
        }

        /**
         * Override to handle security/access resolution on links.
         */
        public function getLinkString($attributeString, $attribute)
        {
            return array($this, 'resolveLinkString');
        }

        /**
         * Resolves the link string for task detail modal view
         * @param array $data
         * @param int $row
         * @return string
         */
        public function resolveLinkString($data, $row)
        {
            $content = TasksUtil::getModalDetailsLink($data, $this->controllerId,
                       $this->moduleId, $this->getActionModuleClassName());
            return $content;
        }

        /**
         * Override to handle security/access resolution on links.
         */
        protected function getCGridViewLastColumn()
        {
            $url  = 'Yii::app()->createUrl("tasks/default/modalEdit", array("id" => $data->id))';
            return array(
                'class'           => 'TaskModalButtonColumn',
                'template'        => '{update}',
                'buttons' => array(
                    'update' => array(
                        'url'             => $url,
                        'imageUrl'        => false,
                        'visible'         => 'ActionSecurityUtil::canCurrentUserPerformAction("Edit", $data)',
                        'options'         => array('class' => 'pencil', 'title' => 'Update'),
                        'label'           => '!',
                        'ajaxOptions'     => TasksUtil::resolveAjaxOptionsForModalView('Edit')
                    ),
                ),
            );
        }

        /**
         * Register the additional script for task detail modal
         */
        protected function renderScripts()
        {
            parent::renderScripts();
            TasksUtil::registerTaskModalDetailsScript($this->getGridViewId());
        }

        protected function getCGridViewAjaxUrl()
        {
            $params = array_merge(GetUtil::getData(), array('portletId' => $this->params['portletId']));
            return Yii::app()->createUrl('home/defaultPortlet/myListDetails', $params);
        }
    }
?>