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

    abstract class ProjectsRelatedListView extends SecuredRelatedListView
    {
        /**
         * The url to use as the redirect url when going to another action. This will return the user
         * to the correct page upon canceling or completing an action.
         * @var string
         */
        public $redirectUrl;

        /**
         * Unique identifier used to identify this view on the page.
         * @var string
         */
        protected $uniquePageId;

        /**
         * Params for the lsit view
         * @var array
         */
        protected $params;

        /**
         * Relation module id
         * @var string
         */
        protected $relationModuleId;

        /**
         * @param array $viewData
         * @param array $params
         * @param string $uniqueLayoutId
         */
        function __construct($viewData, $params, $uniqueLayoutId)
        {
            parent::__construct($viewData, $params, $uniqueLayoutId);
            $this->uniquePageId        = get_called_class();
            $this->relationModuleId    = $this->params['relationModuleId'];
        }

        /**
         * @return array
         */
        public static function getDefaultMetadata()
        {
            $metadata = array(
                    'perUser' => array(
                        'title' => "eval:Zurmo::t('ProjectsModule', 'ProjectsModulePluralLabel', LabelUtil::getTranslationParamsForAllModules())",
                    ),
                    'global' => array(
                        'toolbar' => array(
                            'elements' => array(
                                array(  'type'            => 'CreateFromRelatedListLink',
                                        'routeModuleId'   => 'eval:$this->moduleId',
                                        'routeParameters' => 'eval:$this->getCreateLinkRouteParameters()'),
                            ),
                        ),
                        'rowMenu' => array(
                            'elements' => array(
                                                    array('type'                      => 'ProjectEditLink',
                                                          'relationModuleId'          => 'eval:$this->relationModuleId',
                                                          'relationModelId'           => 'eval:$this->params["relationModel"]->id'),
                                                    array('type'                      => 'RelatedDeleteLink'),
                                                    array('type'                      => 'RelatedUnlink',
                                                          'relationModelClassName'    => 'eval:get_class($this->params["relationModel"])',
                                                          'relationModelId'           => 'eval:$this->params["relationModel"]->id',
                                                          'relationModelRelationName' => 'projects',
                                                          'userHasRelatedModelAccess' => 'eval:ActionSecurityUtil::canCurrentUserPerformAction( "Edit", $this->params["relationModel"])')
                            ),
                        ),
                        'derivedAttributeTypes' => array(),
                        'gridViewType' => RelatedListView::GRID_VIEW_TYPE_NORMAL,
                        'panels' => array(
                            array(
                                'rows' => array(
                                    array('cells' =>
                                        array(
                                            array(
                                                'elements' => array(
                                                    array('attributeName' => 'name', 'type' => 'Text', 'isLink' => true),
                                                ),
                                            ),
                                        )
                                    )
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
            return 'ProjectsModule';
        }
    }
?>