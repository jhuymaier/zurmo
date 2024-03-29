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

    class LeadConvertView extends GridView
    {
        protected $cssClasses =  array('DetailsView');

        protected $controllerId;

        protected $moduleId;

        protected $convertToAccountSetting;

        protected $title;

        protected $modelId;

        public function __construct(
                $controllerId,
                $moduleId,
                $modelId,
                $title,
                $selectAccountform,
                $account,
                $convertToAccountSetting,
                $userCanCreateAccount
            )
        {
            assert('$convertToAccountSetting != LeadsModule::CONVERT_NO_ACCOUNT');
            assert('is_bool($userCanCreateAccount)');

            //if has errors, then show by default
            if ($selectAccountform->hasErrors())
            {
                Yii::app()->clientScript->registerScript('leadConvert', "
                    $(document).ready(function()
                        {
                            $('#AccountConvertToView').hide();
                            $('#LeadConvertAccountSkipView').hide();
                            $('#account-skip-title').hide();
                            $('#account-create-title').hide();
                        }
                    );
                ");
            }
            else
            {
                if ($userCanCreateAccount)
                {
                    Yii::app()->clientScript->registerScript('leadConvert', "
                        $(document).ready(function()
                            {
                                $('#AccountSelectView').hide();
                                $('#LeadConvertAccountSkipView').hide();
                                $('#account-skip-title').hide();
                                $('#account-select-title').hide();
                            }
                        );
                    ");
                }
                else
                {
                    Yii::app()->clientScript->registerScript('leadConvert', "
                        $(document).ready(function()
                            {
                                $('#account-create-title').hide();
                                $('#AccountConvertToView').hide();
                                $('#LeadConvertAccountSkipView').hide();
                                $('#account-skip-title').hide();
                            }
                        );
                    ");
                }
            }
            if ($convertToAccountSetting == LeadsModule::CONVERT_ACCOUNT_NOT_REQUIRED)
            {
                $gridSize = 3;
            }
            else
            {
                $gridSize = 2;
            }
            $title = Zurmo::t('LeadsModule', 'LeadsModuleSingularLabel Conversion',
                                                LabelUtil::getTranslationParamsForAllModules()) . ': ' . $title;
            parent::__construct($gridSize, 1);

            /**
            $x = new LeadConvertActionsView($controllerId, $moduleId, $modelId, $convertToAccountSetting,
                                                      $userCanCreateAccount, $title);
            $this->setView(new LeadConvertActionsView($controllerId, $moduleId, $modelId, $convertToAccountSetting,
                                                      $userCanCreateAccount, $title), 0, 0);
            **/
            $this->setView(new AccountSelectView($controllerId, $moduleId, $modelId, $selectAccountform), 0, 0);
            $this->setView(new AccountConvertToView($controllerId, $moduleId, $account, $modelId), 1, 0);

            if ($convertToAccountSetting == LeadsModule::CONVERT_ACCOUNT_NOT_REQUIRED)
            {
                $this->setView(new LeadConvertAccountSkipView($controllerId, $moduleId, $modelId), 2, 0);
            }

            $this->controllerId            = $controllerId;
            $this->moduleId                = $moduleId;
            $this->modelId                 = $modelId;
            $this->convertToAccountSetting = $convertToAccountSetting;
            $this->userCanCreateAccount    = $userCanCreateAccount;
            $this->title                   = $title;
        }

        /**
         * Renders content for the view.
         * @return A string containing the element's content.
         */
        protected function renderContent()
        {
            Yii::app()->clientScript->registerScript('leadConvertActions', "
                $('.account-select-link').click( function()
                    {
                        $('#AccountConvertToView').hide();
                        $('#LeadConvertAccountSkipView').hide();
                        $('#AccountSelectView').show();
                        $('#account-create-title').hide();
                        $('#account-skip-title').hide();
                        $('#account-select-title').show();
                        return false;
                    }
                );
                $('.account-create-link').click( function()
                    {
                        $('#AccountConvertToView').show();
                        $('#LeadConvertAccountSkipView').hide();
                        $('#AccountSelectView').hide();
                        $('#account-create-title').show();
                        $('#account-skip-title').hide();
                        $('#account-select-title').hide();
                        return false;
                    }
                );
                $('.account-skip-link').click( function()
                    {
                        $('#AccountConvertToView').hide();
                        $('#LeadConvertAccountSkipView').show();
                        $('#AccountSelectView').hide();
                        $('#account-create-title').hide();
                        $('#account-skip-title').show();
                        $('#account-select-title').hide();
                        return false;
                    }
                );
            ");
            $createLink = ZurmoHtml::link(Zurmo::t('AccountsModule', 'Create AccountsModuleSingularLabel',
                            LabelUtil::getTranslationParamsForAllModules()), '#', array('class' => 'account-create-link'));
            $selectLink = ZurmoHtml::link(Zurmo::t('LeadsModule', 'Select AccountsModuleSingularLabel',
                            LabelUtil::getTranslationParamsForAllModules()), '#', array('class' => 'account-select-link'));
            $skipLink   = ZurmoHtml::link(Zurmo::t('LeadsModule', 'Skip AccountsModuleSingularLabel',
                            LabelUtil::getTranslationParamsForAllModules()), '#', array('class' => 'account-skip-link'));
            $content = $this->renderTitleContent();
            $content .= '<div class="lead-conversion-actions">';
            $content .= '<div id="account-select-title">';
            if ($this->userCanCreateAccount)
            {
                $content .= $createLink .  '&#160;' . Zurmo::t('Core', 'or') . '&#160;';
            }
            $content .= Zurmo::t('LeadsModule', 'Select AccountsModuleSingularLabel',
                                    LabelUtil::getTranslationParamsForAllModules()) . '&#160;';

            if ($this->convertToAccountSetting == LeadsModule::CONVERT_ACCOUNT_NOT_REQUIRED)
            {
                $content .= Zurmo::t('Core', 'or') . '&#160;' . $skipLink;
            }
            $content .= '</div>';
            $content .= '<div id="account-create-title">';
            $content .= Zurmo::t('AccountsModule', 'Create AccountsModuleSingularLabel',
                                    LabelUtil::getTranslationParamsForAllModules()) . '&#160;';
            $content .= Zurmo::t('Core', 'or') . '&#160;' . $selectLink . '&#160;';
            if ($this->convertToAccountSetting == LeadsModule::CONVERT_ACCOUNT_NOT_REQUIRED)
            {
                $content .= Zurmo::t('Core', 'or') . '&#160;' . $skipLink;
            }
            $content .= '</div>';
            if ($this->convertToAccountSetting == LeadsModule::CONVERT_ACCOUNT_NOT_REQUIRED)
            {
                $content .= '<div id="account-skip-title">';
                if ($this->userCanCreateAccount)
                {
                    $content .= $createLink . '&#160;' . Zurmo::t('Core', 'or') . '&#160;';
                }
                $content .= $selectLink . '&#160;' . Zurmo::t('Core', 'or') . '&#160;';
                $content .= Zurmo::t('LeadsModule', 'Skip AccountsModuleSingularLabel',
                                        LabelUtil::getTranslationParamsForAllModules()) . '&#160;';
                $content .= '</div>';
            }
            $content .= '</div>'; //this was missing..
            $content  = $content . ZurmoHtml::tag('div', array('class' => 'left-column full-width clearfix'), parent::renderContent());
            return '<div class="wrapper">' . $content . '</div>';
        }

        public function isUniqueToAPage()
        {
            return true;
        }
    }
?>