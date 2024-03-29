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

    class EmailTemplate extends OwnedSecurableItem
    {
        const TYPE_WORKFLOW = 1;

        const TYPE_CONTACT  = 2;

        /**
         * Php caching for a single request
         * @var array
         */
        private static $cachedDataAndLabelsByType = array();

        public static function getByName($name)
        {
            return self::getByNameOrEquivalent('name', $name);
        }

        public static function getModuleClassName()
        {
            return 'EmailTemplatesModule';
        }

        protected static function getLabel($language = null)
        {
            return Zurmo::t('EmailTemplatesModule', 'Email Template', array(), null, $language);
        }

        /**
         * Returns the display name for plural of the model class.
         * @return dynamic label name based on module.
         * @param null | string $language
         */
        protected static function getPluralLabel($language = null)
        {
            return Zurmo::t('EmailTemplatesModule', 'Email Templates', array(), null, $language);
        }

        public static function getTypeDropDownArray()
        {
            return array(
                self::TYPE_WORKFLOW     => Zurmo::t('WorkflowsModule', 'Workflow'),
                self::TYPE_CONTACT      => Zurmo::t('ContactsModule',  'Contact'),
            );
        }

        public static function renderNonEditableTypeStringContent($type)
        {
            assert('is_int($type) || $type == null');
            $dropDownArray = self::getTypeDropDownArray();
            if (!empty($dropDownArray[$type]))
            {
                return Yii::app()->format->text($dropDownArray[$type]);
            }
        }

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

        public static function canSaveMetadata()
        {
            return true;
        }

        public static function isTypeDeletable()
        {
            return true;
        }

        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'type',
                    'modelClassName',
                    'name',
                    'subject',
                    'language',
                    'htmlContent',
                    'textContent',
                ),
                'rules' => array(
                    array('type',                       'required'),
                    array('type',                       'type',    'type' => 'integer'),
                    array('type',                       'numerical'),
                    array('modelClassName',             'required'),
                    array('modelClassName',             'type',   'type' => 'string'),
                    array('modelClassName',             'length', 'max' => 64),
                    array('modelClassName',             'validateModelExists'),
                    array('name',                       'required'),
                    array('name',                       'type',    'type' => 'string'),
                    array('name',                       'length',  'min'  => 1, 'max' => 64),
                    array('subject',                    'required'),
                    array('subject',                    'type',    'type' => 'string'),
                    array('subject',                    'length',  'min'  => 1, 'max' => 64),
                    array('language',                   'type',    'type' => 'string'),
                    array('language',                   'length',  'min' => 2, 'max' => 2),
                    array('language',                   'setToUserDefaultLanguage'),
                    array('htmlContent',                'type',    'type' => 'string'),
                    array('textContent',                'type',    'type' => 'string'),
                    array('htmlContent',                'StripDummyHtmlContentFromOtherwiseEmptyFieldValidator'),
                    array('htmlContent',                'AtLeastOneContentAreaRequiredValidator'),
                    array('textContent',                'AtLeastOneContentAreaRequiredValidator'),
                    array('htmlContent',                'EmailTemplateMergeTagsValidator'),
                    array('textContent',                'EmailTemplateMergeTagsValidator'),
                ),
                'elements' => array(
                    'htmlContent'                   => 'TextArea',
                    'textContent'                   => 'TextArea',
                ),
                'relations' => array(
                    'files'                         => array(static::HAS_MANY,  'FileModel', static::OWNED,
                                                            static::LINK_TYPE_POLYMORPHIC, 'relatedModel'),
                ),
            );
            return $metadata;
        }

        public function validateModelExists($attribute, $params)
        {
            $passedValidation = true;
            $modelClassName = $this->$attribute;
            if (!empty($modelClassName))
            {
                if (@class_exists($modelClassName))
                {
                    if (!is_subclass_of($modelClassName, 'RedBeanModel'))
                    {
                        $this->addError($attribute, Zurmo::t('EmailTemplatesModule', 'Provided class name is not a valid Model class.'));
                        $passedValidation = false;
                    }
                    elseif (!RightsUtil::canUserAccessModule($modelClassName::getModuleClassName(), Yii::app()->user->userModel))
                    {
                        $this->addError($attribute, Zurmo::t('EmailTemplatesModule', 'Provided class name access is prohibited.'));
                        $passedValidation = false;
                    }
                }
                else
                {
                    $this->addError($attribute, Zurmo::t('EmailTemplatesModule', 'Provided class name does not exist.'));
                    $passedValidation = false;
                }
            }
            return $passedValidation;
        }

        public function setToUserDefaultLanguage($attribute, $params)
        {
            if (empty($this->$attribute))
            {
                $this->$attribute = Yii::app()->user->userModel->language;
            }
            return true;
        }

        /**
         * @param $type
         * @return Array of EmailTemplate models
         */
        public static function getByType($type)
        {
            assert('is_int($type)');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'        => 'type',
                    'operatorType'         => 'equals',
                    'value'                => $type,
                ),
            );
            $searchAttributeData['structure'] = '1';
            $joinTablesAdapter                = new RedBeanModelJoinTablesQueryAdapter('EmailTemplate');
            $where = RedBeanModelDataProvider::makeWhere('EmailTemplate', $searchAttributeData, $joinTablesAdapter);
            return self::getSubset($joinTablesAdapter, null, null, $where, 'name');
        }

        /**
         * @param int $type
         * @return array
         */
        public static function getDataAndLabelsByType($type)
        {
            assert('is_int($type)');
            if (isset(self::$cachedDataAndLabelsByType[$type]))
            {
                return self::$cachedDataAndLabelsByType[$type];
            }
            $dataAndLabels = array();
            $emailTemplates = static::getByType($type);
            foreach ($emailTemplates as $emailTemplate)
            {
                $dataAndLabels[$emailTemplate->id] = strval($emailTemplate);
            }
            self::$cachedDataAndLabelsByType[$type] = $dataAndLabels;
            return self::$cachedDataAndLabelsByType[$type];
        }

        public static function getGamificationRulesType()
        {
            return 'EmailTemplateGamification';
        }

        public static function hasReadPermissionsOptimization()
        {
            return true;
        }

        protected static function translatedAttributeLabels($language)
        {
            return array_merge(parent::translatedAttributeLabels($language),
                array(
                    'modelClassName'  => Zurmo::t('Core',                'Module',   null, null, $language),
                    'language'        => Zurmo::t('ZurmoModule',         'Language',   null, null, $language),
                    'htmlContent'     => Zurmo::t('EmailMessagesModule', 'Html Content',  null, null, $language),
                    'name'            => Zurmo::t('ZurmoModule',         'Name',  null, null, $language),
                    'subject'         => Zurmo::t('Core', 'Subject',  null, null, $language),
                    'type'            => Zurmo::t('Core',                'Type',  null, null, $language),
                    'textContent'     => Zurmo::t('EmailMessagesModule', 'Text Content',  null, null, $language),
                )
            );
        }
    }
?>