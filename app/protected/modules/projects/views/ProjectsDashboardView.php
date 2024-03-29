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

    class ProjectsDashboardView extends PortletFrameView
    {
        /**
         * @return array
         */
        public static function getDefaultMetadata()
        {
            $metadata = array(
                'global' => array(
                    'toolbar' => array(),
                    'columns' => array(
                        array(
                            'rows' => array(
                               array(
                                    'type' => 'ActiveProjectsPortlet',
                                ),
                            ),
                          ),
                        array(
                            'rows' => array(
                                array(
                                    'type' => 'ProjectsActivityFeedPortlet',
                                ),
                            )
                        )
                    )
                )
            );
            return $metadata;
        }

        /**
         * @param string $controllerId
         * @param string $moduleId
         * @param string $uniqueLayoutId
         * @param array $params
         */
        public function __construct($controllerId, $moduleId, $uniqueLayoutId, $params)
        {
            $this->controllerId        = $controllerId;
            $this->moduleId            = $moduleId;
            $this->uniqueLayoutId      = $uniqueLayoutId;
            $this->layoutType          = '50,50'; // Not Coding Standard
            $this->params              = $params;
        }

        /**
         * @return bool
         */
        public function isUniqueToAPage()
        {
            return true;
        }

        /**
         * Override to always block portlets from being removable, collapsable, and configurable
         * @return mixed
         */
        protected function renderContent()
        {
            $this->portlets = $this->getPortlets($this->uniqueLayoutId, self::getMetadata());
            return $this->renderPortlets($this->uniqueLayoutId, false, false, false);
        }

        /**
         * Override to allow for making a default set of portlets
         * via metadata optional.
         * @param string $uniqueLayoutId
         * @param array $metadata
         * @return array
         */
        protected function getPortlets($uniqueLayoutId, $metadata)
        {
            assert('is_string($uniqueLayoutId)');
            assert('is_array($metadata)');
            $portlets = Portlet::getByLayoutIdAndUserSortedByColumnIdAndPosition($uniqueLayoutId, Yii::app()->user->userModel->id, $this->params);
            if (empty($portlets))
            {
                $portlets = Portlet::makePortletsUsingMetadataSortedByColumnIdAndPosition($uniqueLayoutId, $metadata, Yii::app()->user->userModel, $this->params);
                Portlet::savePortlets($portlets);
            }
            return PortletsSecurityUtil::resolvePortletsForCurrentUser($portlets);
        }
    }
?>
