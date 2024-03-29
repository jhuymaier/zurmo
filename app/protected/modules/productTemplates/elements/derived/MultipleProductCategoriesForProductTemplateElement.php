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
     * User interface element for managing related model relations for product templates. This class supports a HAS_MANY
     * specifically for the 'productCategories' relation. This is utilized by the Product Template model.
     *
     */
    class MultipleProductCategoriesForProductTemplateElement extends MultiSelectRelatedModelsAutoCompleteElement
    {
        protected function getFormName()
        {
            return 'ProductTemplateCategoriesForm';
        }

        /**
         * @return string
         */
        protected function getUnqualifiedNameForIdField()
        {
            return '[categoryIds]';
        }

        /**
         * @return string
         */
        protected function getUnqualifiedIdForIdField()
        {
            return '_ProductCategory_ids';
        }

        /**
         * @return string
         */
        protected function getFormattedAttributeLabel()
        {
            return Yii::app()->format->text(Zurmo::t('ProductTemplatesModule', 'Categories'));
        }

        /**
         * @return string
         */
        public static function getDisplayName()
        {
            return Zurmo::t('ProductTemplatesModule', 'Related ProductTemplatesModulePluralLabel',
                                                        LabelUtil::getTranslationParamsForAllModules());
        }

        protected function assertModelType()
        {
            assert('$this->model instanceof ProductTemplate || $this->model instanceof Product');
        }

        protected function getWidgetHintText()
        {
            return Zurmo::t('ProductTemplatesModule', 'Type a ' .
                                                        LabelUtil::getUncapitalizedModelLabelByCountAndModelClassName(1,
                                                                                                    'ProductCategory'),
                                                    LabelUtil::getTranslationParamsForAllModules());
        }

        protected function getWidgetSourceUrl()
        {
            return Yii::app()->createUrl('productTemplates/default/autoCompleteAllProductCategoriesForMultiSelectAutoComplete');
        }

        protected function getRelationName()
        {
            return 'productCategories';
        }

        /**
         * @param object $productCategory
         * @param string $keyword
         * @return string
         */
        public static function renderHtmlContentLabelFromProductCategoryAndKeyword($productCategory)
        {
            assert('$productCategory instanceof ProductCategory && $productCategory->id > 0');
            if ($productCategory->name != null)
            {
                return strval($productCategory) . '&#160&#160<b>'. '</b>';
            }
            else
            {
                return strval($productCategory);
            }
        }
    }
?>