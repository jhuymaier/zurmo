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
     * Module form for changing module specific settings such
     * as the module label name.
     */
    abstract class ModuleForm extends ConfigurableMetadataModel
    {
        public $singularModuleLabels   = array();
        public $pluralModuleLabels     = array();

        public function __construct()
        {
        }

        public function rules()
        {
            return array(
                array('singularModuleLabels', 'validateModuleLabels'),
                array('pluralModuleLabels',   'validateModuleLabels'),
            );
        }

        public function attributeLabels()
        {
            return array(
                'singularModuleLabels'  => Zurmo::t('ZurmoModule', 'Module Name - Singular (lowercase)'),
                'pluralModuleLabels'    => Zurmo::t('ZurmoModule', 'Module Name - Plural (lowercase)'),
            );
        }

        public function validateModuleLabels($attribute, $params)
        {
            $data = $this->$attribute;
            foreach (Yii::app()->languageHelper->getActiveLanguagesData() as $language => $notUsed)
            {
                if ( empty($data[$language]))
                {
                    $this->addError($attribute . '[' . $language . ']', Zurmo::t('Core', 'Label must not be empty.'));
                }
                if ($data[$language] != TextUtil::strToLowerWithDefaultEncoding($data[$language]))
                {
                    $this->addError($attribute . '[' . $language . ']',
                                Zurmo::t('ZurmoModule', 'Label must be all lowercase.'));
                }
                if (!preg_match('/^[\p{L}A-Za-z0-9_ ]+$/u', $data[$language])) // Not Coding Standard
                {
                    $this->addError($attribute . '[' . $language . ']',
                        Zurmo::t('ZurmoModule', 'Label must not contain any special characters.'));
                }
            }
        }
    }
?>