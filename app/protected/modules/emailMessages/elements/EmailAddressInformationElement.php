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
     * Display the email address collection
     * which includes an email address, opt out boolean
     * and invalid boolean.
     */
    class EmailAddressInformationElement extends Element
    {
        /**
         * Renders the editable email address content.
         * Takes the model attribute value and converts it into
         * at most 3 items. Email Address display, Opt Out checkbox,
         * and Invalid Email checkbox.
         * @return A string containing the element's content
         */
        protected function renderControlEditable()
        {
            assert('$this->model->{$this->attribute} instanceof Email');
            $addressModel = $this->model->{$this->attribute};
            $content      = $this->renderEditableEmailAddressTextField    ($addressModel, $this->form, $this->attribute, 'emailAddress') . "\n";

            if (ArrayUtil::getArrayValue($this->params, 'hideOptOut') != true)
            {
                $content      = ZurmoHtml::tag('div', array('class' => 'beforeOptOutCheckBox'), '<div>' . $content . '</div>');
                $content     .= $this->renderEditableEmailAddressCheckBoxField($addressModel, $this->form, $this->attribute, 'optOut') . "\n";
            }
            return $content;
        }

        protected function renderEditableEmailAddressTextField($model, $form, $inputNameIdPrefix, $attribute)
        {
            $id = $this->getEditableInputId($inputNameIdPrefix, $attribute);
            $htmlOptions = array(
                'name' => $this->getEditableInputName($inputNameIdPrefix, $attribute),
                'id'   => $id,
            );
            $textField = $form->textField($model, $attribute, $htmlOptions);
            $error     = $form->error    ($model, $attribute, array('inputID' => $id), true, true,
                                          $this->renderScopedErrorId($inputNameIdPrefix, $attribute));
            return $textField . $error;
        }

        protected function renderEditableEmailAddressCheckBoxField($model, $form, $inputNameIdPrefix, $attribute)
        {
            $id = $this->getEditableInputId($inputNameIdPrefix, $attribute);
            $htmlOptions = array(
                'name' => $this->getEditableInputName($inputNameIdPrefix, $attribute),
                'id'   => $id,
            );
            $label         = $form->labelEx ($model, $attribute, array('for'   => $id));
            $checkBoxField = $form->checkBox($model, $attribute, $htmlOptions);
            $error         = $form->error   ($model, $attribute, array('inputID' => $id), true, true,
                                             $this->renderScopedErrorId($inputNameIdPrefix, $attribute));
            return '<div class="hasCheckBox">' . $checkBoxField . $label . $error . '</div>';
        }

        /**
         * Renders the noneditable email address content.
         * If the model is a person, and the user accessing this element has right to access the email module,
         * then the email address will be clickable.  When clicked it will open a modal create email window.
         * Takes the model attribute value and converts it into
         * at most 3 items. Email Address display, Opt Out checkbox,
         * and Invalid Email checkbox.
         * @return A string containing the element's content.
         */
        protected function renderControlNonEditable()
        {
            $addressModel    = $this->model->{$this->attribute};
            $emailAddress    = $addressModel->emailAddress;
            $optOut          = $addressModel->optOut;
            $isInvalid       = $addressModel->isInvalid;
            $content = null;
            if (!empty($emailAddress))
            {
                $content .= EmailMessageUtil::renderEmailAddressAsMailToOrModalLinkStringContent($emailAddress, $this->model);
                if ($optOut || $isInvalid)
                {
                    $content  .= '&#160;&#40;';
                }
                if ($optOut)
                {
                    $content  .= Zurmo::t('EmailMessagesModule', 'Opted Out');
                }
                if ($isInvalid)
                {
                    if ($optOut)
                    {
                        $content  .= ',&#160;';
                    }
                    $content  .= Zurmo::t('Core', 'Invalid');
                }
                if ($optOut || $isInvalid)
                {
                    $content  .= '&#41;';
                }
            }
            return $content;
        }

        protected function renderError()
        {
        }

        protected function renderLabel()
        {
            if ($this->form === null)
            {
                return $this->getFormattedAttributeLabel();
            }
            $id = $this->getEditableInputId($this->attribute, 'emailAddress');
            return $this->form->labelEx($this->model, $this->attribute, array('for' => $id));
        }
    }
?>
