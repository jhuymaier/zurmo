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
     * Import wizard step to allow users to pick the permissions on the new models that will be imported.
     */
    class ImportWizardSetModelPermissionsView extends ImportWizardView
    {
        /**
         * Override to handle the form layout for this view.
         * @param $form If the layout is editable, then pass a $form otherwise it can
         * be null.
         * @return A string containing the element's content.
          */
        protected function renderFormLayout($form = null)
        {
            assert('$form instanceof ZurmoActiveForm');

            $importModelClassName      = ImportRulesUtil::getImportRulesClassNameByType($this->model->importRulesType);
            $importRulesLabel          = $importModelClassName::getDisplayLabel();
            $label                     = '<h3>' . Zurmo::t('ImportModule', 'Who can read and write the new {importRulesLabel}',
                                                array('{importRulesLabel}' => $importRulesLabel)) . '</h3>';
            $element                   = new ExplicitReadWriteModelPermissionsElement($this->model,
                                             'explicitReadWriteModelPermissions', $form);
            $element->editableTemplate = $label . '{content}';

            $content  = $form->errorSummary($this->model);
            $content .= ZurmoHtml::tag('div', array('class' => 'right-side-edit-view-panel'), $element->render());
            return $content;
        }

        protected function renderPreviousPageLinkContent()
        {
            return $this->getPreviousPageLinkContentByControllerAction('step2');
        }

        protected function renderPreviousPageLinkLabel()
        {
            return Zurmo::t('ZurmoModule', 'Upload File');
        }

        protected function renderNextPageLinkLabel()
        {
            return Zurmo::t('ZurmoModule', 'Map Fields');
        }
    }
?>