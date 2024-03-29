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

    class ActiveProjectsListView extends SecuredListView
    {
        /**
         * Renders content for a list view. Utilizes a CActiveDataprovider
         * and a CGridView widget.
         * and form layout.
         * @return A string containing the element's content.
         */
        protected function renderContent()
        {
            $this->setRowsAreSelectable(false);
            return parent::renderContent();
        }

        /**
         * @return array
         */
        public static function getDefaultMetadata()
        {
            $metadata = array(
                'global' => array(
                    'panels' => array(
                        array(
                            'rows' => array(
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'name', 'type' => 'DashboardActiveProject'),
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
         * @return array
         */
        protected function getCGridViewPagerParams()
        {
            return array(
                    'firstPageLabel'   => '<span>first</span>',
                    'prevPageLabel'    => '<span>previous</span>',
                    'nextPageLabel'    => '<span>next</span>',
                    'lastPageLabel'    => '<span>last</span>',
                    'paginationParams' => GetUtil::getData(),
                    'route'            => $this->getGridViewActionRoute($this->getListActionId(), $this->moduleId),
                    'class'            => 'SimpleListLinkPager',
                );
        }

        /**
         * @return string
         */
        protected static function getPagerCssClass()
        {
            return 'pager horizontal';
        }

        /**
         * @return string
         */
        protected static function getSummaryText()
        {
            return Zurmo::t('Core', '{start}-{end} of {count} result(s).');
        }

        /**
         * @return string
         */
        protected function getListActionId()
        {
            return 'showActiveProjects';
        }

        /**
         * Checks if header cells have to be hidden
         * @return bool
         */
        protected function isHeaderHidden()
        {
            return true;
        }

        /**
         * @return string
         */
        public function getGridViewId()
        {
            return 'active-projects-list-view';
        }

        /**
         * Get grid params
         * @return array
         */
        protected function getCGridViewParams()
        {
            return array_merge(parent::getCGridViewParams(),
                        array('hideHeader'            => true,
                              'itemsCssClass'         => 'items stacked-list',
                              'renderSpanOnEmptyText' => false));
        }

        /**
         * Get text in case there are no projects
         * @return string
         */
        protected function getEmptyText()
        {
            $content  = '<div class="general-issue-notice no-projects-found"><span class="icon-notice"></span><p>';
            $content .= Zurmo::t('ProjectsModule', 'No Projects created');
            $content .= '</p></div>';
            return $content;
        }

        public static function getDesignerRulesType()
        {
            return 'NonModifiableListView';
        }
    }
?>
