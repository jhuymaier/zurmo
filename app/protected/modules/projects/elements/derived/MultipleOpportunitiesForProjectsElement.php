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
     * User interface element for managing related model relations for projects. This class supports a MANY_MANY
     * specifically for the 'opportunities' relation. This is utilized by the Project model.
     *
     */
    class MultipleOpportunitiesForProjectsElement extends MultiSelectRelatedModelsAutoCompleteElement
    {
        /**
         * @return string
         */
        protected function getFormName()
        {
            return 'ProjectOpportunitiesForm';
        }

        /**
         * @return string
         */
        protected function getUnqualifiedNameForIdField()
        {
            return '[opportunityIds]';
        }

        /**
         * @return string
         */
        protected function getUnqualifiedIdForIdField()
        {
            return '_Opportunity_ids';
        }

        /**
         * @return string
         */
        protected function assertModelType()
        {
            assert('$this->model instanceof Project');
        }

        /**
         * @return string
         */
        protected function getWidgetSourceUrl()
        {
            return Yii::app()->createUrl('projects/default/autoCompleteAllOpportunitiesForMultiSelectAutoComplete');
        }

        /**
         * @return string
         */
        protected function getWidgetHintText()
        {
            return Zurmo::t('ProjectsModule', 'Type a ' .
                                                LabelUtil::getUncapitalizedModelLabelByCountAndModelClassName(1, 'Opportunity'),
                                            LabelUtil::getTranslationParamsForAllModules());
        }

        /**
         * @return string
         */
        protected function getRelationName()
        {
            return 'opportunities';
        }

        /**
         * @return string
         */
        protected function getFormattedAttributeLabel()
        {
            return Yii::app()->format->text($this->model->getAttributeLabel('opportunities'));
        }

        /**
         * @return string
         */
        public static function getDisplayName()
        {
            return Zurmo::t('OpportunitiesModule', 'Related OpportunitiesModulePluralLabel',
                                                    LabelUtil::getTranslationParamsForAllModules());
        }
    }
?>