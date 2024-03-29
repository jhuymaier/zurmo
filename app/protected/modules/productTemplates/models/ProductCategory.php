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

    class ProductCategory extends Item
    {
        const EVERYONE_CATEGORY_NAME            = 'Everyone';

        const ERROR_EXIST_TEMPLATE              = 1;

        const ERROR_EXIST_CHILD_CATEGORIES      = 2;

        /**
         * @param string $name
         * @return string
         */
        public static function getByName($name)
        {
            return self::getByNameOrEquivalent('name', $name);
        }

        /**
         * @return array
         */
        protected function untranslatedAttributeLabels()
        {
            return array_merge(parent::untranslatedAttributeLabels(),
                array(
                )
            );
        }

        /**
         * @return string
         */
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

        /**
         * @return string
         */
        public static function getModuleClassName()
        {
            return 'ProductTemplatesModule';
        }

        /**
         * Returns the display name for the model class.
         * @param null}string $language
         * @return dynamic label name based on module.
         */
        protected static function getLabel($language = null)
        {
            return Zurmo::t('ProductTemplatesModule', 'Product Category', array(), null, $language);
        }

        /**
         * Returns the display name for plural of the model class.
         * @param null}string $language
         * @return dynamic label name based on module.
         */
        protected static function getPluralLabel($language = null)
        {
            return Zurmo::t('ProductTemplatesModule', 'Product Categories', array(), null, $language);
        }

        /**
         * @return bool
         */
        public static function canSaveMetadata()
        {
            return true;
        }

        /**
         * @return array
         */
        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'name'
                ),
                'relations' => array(
                    'productTemplates'  => array(static::MANY_MANY, 'ProductTemplate'),
                    'products'          => array(static::MANY_MANY, 'Product'),
                    'productCatalogs'   => array(static::MANY_MANY, 'ProductCatalog'),
                    'productCategory'   => array(static::HAS_MANY_BELONGS_TO, 'ProductCategory'),
                    'productCategories' => array(static::HAS_MANY, 'ProductCategory'),
                ),
                'rules' => array(
                    array('name',  'required'),
                    array('name',  'type',    'type' => 'string'),
                    array('name',  'length',  'min'  => 1,  'max' => 64),
                ),
                'elements' => array(
                ),
                'customFields' => array(
                ),
                'defaultSortAttribute' => 'name',
                'noAudit' => array(
                ),
            );
            return $metadata;
        }

        /**
         * @return bool
         */
        public static function isTypeDeletable()
        {
            return true;
        }

        /**
         * @return string
         */
        public static function getRollUpRulesType()
        {
            return 'ProductCategory';
        }

        public static function getGamificationRulesType()
        {
            //return 'ProductCategoryGamification';
        }

        /**
         * @return string
         */
        protected function beforeDelete()
        {
            parent::beforeDelete();
            return !(count($this->productTemplates) > 0 || count($this->productCategories) > 0 );
        }

        /**
         * @return array
         */
        protected static function translatedAttributeLabels($language)
        {
            return array_merge(parent::translatedAttributeLabels($language), array(
                'productCategory'   => Zurmo::t('ProductTemplatesModule', 'Parent ' . self::getModelLabelByTypeAndLanguage('Singular', $language), array(), null, $language),
                'productCategories' => self::getModelLabelByTypeAndLanguage('Plural', $language),
                'productCatalogs'   => ProductCatalog::getModelLabelByTypeAndLanguage('Plural', $language),
                'products'          => Zurmo::t('ProductsModule', 'ProductsModulePluralLabel', array(), null, $language),
                'productTemplates'  => Zurmo::t('ProductTemplatesModule', 'ProductTemplatesModulePluralLabel', array(), null, $language)
            ));
        }
    }
?>