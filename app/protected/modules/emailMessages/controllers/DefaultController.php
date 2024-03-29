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
     * Controller for managing configuration actions for email messages
     */
    class EmailMessagesDefaultController extends ZurmoBaseController
    {
        const USER_EMAIL_CONFIGURATION_FILTER_PATH =
              'application.modules.emailMessages.controllers.filters.UserEmailConfigurationCheckControllerFilter';

        const EMAIL_MESSAGES_REQUIRING_ARCHIVING_CONFIGURATION_FILTER_PATH =
              'application.modules.emailMessages.controllers.filters.EmailMessagesRequiringArchivingCheckControllerFilter';

        public function filters()
        {
            $moduleClassName = get_class($this->getModule());
            return array(
                array(
                    ZurmoBaseController::ADMIN_VIEW_MOBILE_CHECK_FILTER_PATH . ' + configurationEdit, configurationEditOutbound, actionConfigurationEditImap',
                ),
                array(
                    ZurmoBaseController::RIGHTS_FILTER_PATH . ' + configurationEdit, configurationEditOutbound, actionConfigurationEditImap',
                    'moduleClassName' => $moduleClassName,
                    'rightName'       => EmailMessagesModule::RIGHT_ACCESS_CONFIGURATION,
               ),
               array(
                    self::getRightsFilterPath() . ' + createEmailMessage',
                    'moduleClassName' => $moduleClassName,
                    'rightName' => $moduleClassName::getCreateRight(),
                ),
                array(self::USER_EMAIL_CONFIGURATION_FILTER_PATH . ' + createEmailMessage',
                     'controller' => $this,
                ),
                array(self::EMAIL_MESSAGES_REQUIRING_ARCHIVING_CONFIGURATION_FILTER_PATH . ' + matchingList' ,
                     'controller' => $this,
                )
            );
        }

        public function actionEdit($id, $redirectUrl = null)
        {
            $emailMessage = EmailMessage::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserWriteModel($emailMessage);
            $this->processEdit($emailMessage, $redirectUrl);
        }

        protected function processEdit(EmailMessage $emailMessage, $redirectUrl = null)
        {
            $view = new EmailMessagesPageView(ZurmoDefaultViewUtil::
                        makeStandardViewForCurrentUser($this, $this->makeEditAndDetailsView(
                            $this->attemptToSaveModelFromPost($emailMessage, $redirectUrl), 'Edit')));
            echo $view->render();
        }

        public function actionDetails($id, $redirectUrl = null)
        {
            $emailMessage          = EmailMessage::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($emailMessage);
            $detailsView           = new EmailMessageEditAndDetailsView('Details', $this->getId(),
                                     $this->getModule()->getId(), $emailMessage);
            $view                  = new EmailMessagesPageView(ZurmoDefaultViewUtil::
                                         makeStandardViewForCurrentUser($this, $detailsView));
            echo $view->render();
        }

        public function actionConfigurationEdit()
        {
            $breadCrumbLinks = array(
                Zurmo::t('EmailMessagesModule', 'Email Configuration'),
            );
            $view = new ConfigurationPageView(ZurmoDefaultAdminViewUtil::makeViewWithBreadcrumbsForCurrentUser(
                        $this, new EmailConfigurationListView(), $breadCrumbLinks, 'SettingsBreadCrumbView'));
            echo $view->render();
        }

        public function actionConfigurationEditOutbound()
        {
            $breadCrumbLinks = array(
                Zurmo::t('EmailMessagesModule', 'Email Configuration') => array('/emailMessages/default/configurationEdit'),
                Zurmo::t('EmailMessagesModule', 'Outbound Email Configuration (SMTP)')
            );
            $configurationForm = EmailSmtpConfigurationFormAdapter::makeFormFromGlobalConfiguration();
            $postVariableName   = get_class($configurationForm);
            if (isset($_POST[$postVariableName]))
            {
                $configurationForm->setAttributes($_POST[$postVariableName]);
                if ($configurationForm->validate())
                {
                    EmailSmtpConfigurationFormAdapter::setConfigurationFromForm($configurationForm);
                    Yii::app()->user->setFlash('notification',
                        Zurmo::t('EmailMessagesModule', 'Email configuration saved successfully.')
                    );
                    $this->redirect(Yii::app()->createUrl('configuration/default/index'));
                }
            }
            $editView = new EmailSmtpConfigurationEditAndDetailsView(
                                    'Edit',
                                    $this->getId(),
                                    $this->getModule()->getId(),
                                    $configurationForm);
            $editView->setCssClasses( array('AdministrativeArea') );
            $view = new ZurmoConfigurationPageView(ZurmoDefaultAdminViewUtil::makeViewWithBreadcrumbsForCurrentUser(
                    $this, $editView, $breadCrumbLinks, 'SettingsBreadCrumbView'));
            echo $view->render();
        }

        public function actionConfigurationEditImap($type = 1)
        {
            $type   = intval($type);
            if ($type < 1 || $type > 2 )
            {
                throw new CHttpException(400);
            }
            $adapterClassName   = 'EmailArchivingConfigurationFormAdapter';
            $editViewClassName  = 'EmailArchivingConfigurationEditAndDetailsView';
            $successMessage     = Zurmo::t('EmailMessagesModule', 'Email archiving configuration saved successfully.');
            $breadCrumbLinks = array(Zurmo::t('EmailMessagesModule', 'Email Configuration')
                                     => array('/emailMessages/default/configurationEdit'));
            if ($type == 2)
            {
                $adapterClassName   = 'BounceConfigurationFormAdapter';
                $editViewClassName  = 'BounceConfigurationEditAndDetailsView';
                $successMessage     = Zurmo::t('EmailMessagesModule', 'Bounce configuration saved successfully.');
                $breadCrumbLinks[]  = Zurmo::t('EmailMessagesModule', 'Bounce Configuration (IMAP)');
            }
            else
            {
                $breadCrumbLinks[] = Zurmo::t('EmailMessagesModule', 'Email Archiving Configuration (IMAP)');
            }
            $configurationForm = $adapterClassName::makeFormFromGlobalConfiguration();
            $postVariableName   = get_class($configurationForm);
            if (isset($_POST[$postVariableName]))
            {
                $configurationForm->setAttributes($_POST[$postVariableName]);
                if ($configurationForm->validate())
                {
                    $adapterClassName::setConfigurationFromForm($configurationForm);
                    Yii::app()->user->setFlash('notification', $successMessage);
                    $this->redirect(Yii::app()->createUrl('configuration/default/index'));
                }
            }
            $editView = new $editViewClassName(
                                    'Edit',
                                    $this->getId(),
                                    $this->getModule()->getId(),
                                    $configurationForm);
            $editView->setCssClasses( array('AdministrativeArea') );
            $view = new ZurmoConfigurationPageView(ZurmoDefaultAdminViewUtil::makeViewWithBreadcrumbsForCurrentUser(
                        $this, $editView, $breadCrumbLinks, 'SettingsBreadCrumbView'));
            echo $view->render();
        }

        /**
         * Assumes before calling this, the outbound settings have been validated in the form.
         * Todo: When new user interface is complete, this will be re-worked to be on page instead of modal.
         */
        public function actionSendTestMessage()
        {
            $configurationForm = EmailSmtpConfigurationFormAdapter::makeFormFromGlobalConfiguration();
            $postVariableName   = get_class($configurationForm);
            if (isset($_POST[$postVariableName]) || (isset($_POST['UserEmailConfigurationForm'])))
            {
                if (isset($_POST[$postVariableName]))
                {
                    $configurationForm->setAttributes($_POST[$postVariableName]);
                }
                else
                {
                    $configurationForm->host            = $_POST['UserEmailConfigurationForm']['outboundHost'];
                    $configurationForm->port            = $_POST['UserEmailConfigurationForm']['outboundPort'];
                    $configurationForm->username        = $_POST['UserEmailConfigurationForm']['outboundUsername'];
                    $configurationForm->password        = $_POST['UserEmailConfigurationForm']['outboundPassword'];
                    $configurationForm->security        = $_POST['UserEmailConfigurationForm']['outboundSecurity'];
                    $configurationForm->aTestToAddress  = $_POST['UserEmailConfigurationForm']['aTestToAddress'];
                    $fromNameToSendMessagesFrom         = $_POST['UserEmailConfigurationForm']['fromName'];
                    $fromAddressToSendMessagesFrom      = $_POST['UserEmailConfigurationForm']['fromAddress'];
                }
                if ($configurationForm->aTestToAddress != null)
                {
                    $emailHelper = new EmailHelper();
                    $emailHelper->loadDefaultFromAndToAddresses();
                    $emailHelper->outboundHost     = $configurationForm->host;
                    $emailHelper->outboundPort     = $configurationForm->port;
                    $emailHelper->outboundUsername = $configurationForm->username;
                    $emailHelper->outboundPassword = $configurationForm->password;
                    $emailHelper->outboundSecurity = $configurationForm->security;
                    if (isset($fromNameToSendMessagesFrom) && isset($fromAddressToSendMessagesFrom))
                    {
                        $from = array(
                            'name'      => $fromNameToSendMessagesFrom,
                            'address'   => $fromAddressToSendMessagesFrom
                        );
                        $emailMessage = EmailMessageHelper::sendTestEmail($emailHelper, $from,
                                                                      $configurationForm->aTestToAddress);
                    }
                    else
                    {
                        $user                   = BaseControlUserConfigUtil::getUserToRunAs();
                        $userToSendMessagesFrom = User::getById((int)$user->id);
                        $emailMessage = EmailMessageHelper::sendTestEmailFromUser($emailHelper, $userToSendMessagesFrom,
                                                                      $configurationForm->aTestToAddress);
                    }
                    $messageContent  = null;
                    if (!($emailMessage->hasErrors() || $emailMessage->hasSendError()))
                    {
                        $messageContent .= Zurmo::t('EmailMessagesModule', 'Message successfully sent') . "\n";
                    }
                    else
                    {
                        $messageContent .= Zurmo::t('EmailMessagesModule', 'Message failed to send') . "\n";
                        if ($emailMessage->hasSendError())
                        {
                            $messageContent .= $emailMessage->error     . "\n";
                        }
                        else
                        {
                            $errors = $emailMessage->getErrors();
                            $data = array();
                            foreach ($errors as $attributeNameWithErrors)
                            {
                                foreach ($attributeNameWithErrors as $attributeError)
                                {
                                    $messageContent .= reset($attributeError) . "\n";
                                }
                            }
                        }
                    }
                }
                else
                {
                    $messageContent = Zurmo::t('EmailMessagesModule', 'A test email address must be entered before you can send a test email.') . "\n";
                }
                Yii::app()->getClientScript()->setToAjaxMode();
                $messageView = new TestConnectionView($messageContent);
                $view = new ModalView($this, $messageView);
                echo $view->render();
            }
            else
            {
                throw new NotSupportedException();
            }
        }

        /**
         * Assumes before calling this, the inbound settings have been validated in the form.
         */
        public function actionTestImapConnection($type = 1)
        {
            $type   = intval($type);
            if ($type < 1 || $type > 2 )
            {
                throw new CHttpException(400);
            }
            $adapterClassName   = 'EmailArchivingConfigurationFormAdapter';
            if ($type == 2)
            {
                $adapterClassName   = 'BounceConfigurationFormAdapter';
            }
            $configurationForm = $adapterClassName::makeFormFromGlobalConfiguration();
            $postVariableName   = get_class($configurationForm);
            if (isset($_POST[$postVariableName]))
            {
                $configurationForm->setAttributes($_POST[$postVariableName]);

                $imap = new ZurmoImap();

                $imap->imapHost     = $configurationForm->imapHost;
                $imap->imapUsername = $configurationForm->imapUsername;
                $imap->imapPassword = $configurationForm->imapPassword;
                $imap->imapPort     = $configurationForm->imapPort;
                $imap->imapSSL      = $configurationForm->imapSSL;
                $imap->imapFolder   = $configurationForm->imapFolder;

                try
                {
                    $connect = $imap->connect();
                }
                catch (Exception $e)
                {
                    $connect = false;
                }
                if ($connect)
                {
                    $messageContent = Zurmo::t('EmailMessagesModule', 'Successfully connected to IMAP server.') . "\n";
                }
                else
                {
                    $messageContent = Zurmo::t('EmailMessagesModule', 'Could not connect to IMAP server.') . "\n";
                }
                Yii::app()->getClientScript()->setToAjaxMode();
                $messageView = new TestConnectionView($messageContent);
                $view = new ModalView($this,
                                      $messageView,
                                      'modalContainer',
                                      Zurmo::t('EmailMessagesModule', 'Test Message Results')
                );
                echo $view->render();
            }
            else
            {
                throw new NotSupportedException();
            }
        }

        public function actionMatchingList()
        {
            $userCanAccessContacts = RightsUtil::canUserAccessModule('ContactsModule', Yii::app()->user->userModel);
            $userCanAccessLeads    = RightsUtil::canUserAccessModule('LeadsModule', Yii::app()->user->userModel);
            EmailMessagesControllerSecurityUtil::
                resolveCanUserProperlyMatchMessage($userCanAccessContacts, $userCanAccessLeads);
            $pageSize         = Yii::app()->pagination->resolveActiveForCurrentUserByType(
                                'listPageSize', get_class($this->getModule()));
            $emailMessage     = new EmailMessage(false);
            $searchAttributes = array();
            $metadataAdapter  = new ArchivedEmailMatchingSearchDataProviderMetadataAdapter(
                $emailMessage,
                Yii::app()->user->userModel->id,
                $searchAttributes
            );
            $dataProvider = RedBeanModelDataProviderUtil::makeDataProvider(
                $metadataAdapter->getAdaptedMetadata(),
                'EmailMessage',
                'RedBeanModelDataProvider',
                'createdDateTime',
                true,
                $pageSize
            );
            $titleBarAndListView = new TitleBarAndListView(
                                        $this->getId(),
                                        $this->getModule()->getId(),
                                        $emailMessage,
                                        'EmailMessage',
                                        $dataProvider,
                                        'ArchivedEmailMatchingListView',
                                        Zurmo::t('EmailMessagesModule', 'Unmatched Archived Emails'),
                                        array(),
                                        false);
            $view = new EmailMessagesPageView(ZurmoDefaultViewUtil::
                                              makeStandardViewForCurrentUser($this, $titleBarAndListView));
            echo $view->render();
        }

        public function actionCompleteMatch($id)
        {
            //!!!todo security checks?? think about it
            $emailMessage          = EmailMessage::getById((int)$id);
            $userCanAccessContacts = RightsUtil::canUserAccessModule('ContactsModule', Yii::app()->user->userModel);
            $userCanAccessLeads    = RightsUtil::canUserAccessModule('LeadsModule', Yii::app()->user->userModel);
            if (!$userCanAccessContacts && !$userCanAccessLeads)
            {
                throw new NotSupportedException();
            }
            $selectForm            = self::makeSelectForm($userCanAccessLeads, $userCanAccessContacts);

            if (isset($_POST[get_class($selectForm)]))
            {
                if (isset($_POST['ajax']) && $_POST['ajax'] === 'select-contact-form-' . $id)
                {
                    $selectForm->setAttributes($_POST[get_class($selectForm)][$id]);
                    $selectForm->validate();
                    $errorData = array();
                    foreach ($selectForm->getErrors() as $attribute => $errors)
                    {
                            $errorData[ZurmoHtml::activeId($selectForm, $attribute)] = $errors;
                    }
                    echo CJSON::encode($errorData);
                    Yii::app()->end(0, false);
                }
                else
                {
                    $selectForm->setAttributes($_POST[get_class($selectForm)][$id]);
                    $contact = Contact::getById((int)$selectForm->contactId);
                    ArchivedEmailMatchingUtil::resolveContactToSenderOrRecipient($emailMessage, $contact);
                    $emailMessage->folder = EmailFolder::getByBoxAndType($emailMessage->folder->emailBox,
                                                                         EmailFolder::TYPE_ARCHIVED);
                    if (!$emailMessage->save())
                    {
                        throw new FailedToSaveModelException();
                    }
                }
            }
            else
            {
                static::attemptToMatchAndSaveLeadOrContact($emailMessage, 'Contact', (int)$id);
                static::attemptToMatchAndSaveLeadOrContact($emailMessage, 'Lead', (int)$id);
            }
            ZurmoControllerUtil::updatePermissionsWithDefaultForModelByCurrentUser($emailMessage);
        }

        protected static function attemptToMatchAndSaveLeadOrContact($emailMessage, $type, $emailMessageId)
        {
            assert('$type == "Contact" || $type == "Lead"');
            assert('is_int($emailMessageId)');
            if (isset($_POST[$type]))
            {
                if (isset($_POST['ajax']) && $_POST['ajax'] === strtolower($type) . '-inline-create-form-' . $emailMessageId)
                {
                    $contact = new Contact();
                    $contact->setAttributes($_POST[$type][$emailMessageId]);
                    $contact->validate();
                    $errorData = ZurmoActiveForm::makeErrorsDataAndResolveForOwnedModelAttributes($contact);
                    echo CJSON::encode($errorData);
                    Yii::app()->end(0, false);
                }
                else
                {
                    $contact = new Contact();
                    $contact->setAttributes($_POST[$type][$emailMessageId]);
                    if (!$contact->save())
                    {
                        throw new FailedToSaveModelException();
                    }
                    ArchivedEmailMatchingUtil::resolveContactToSenderOrRecipient($emailMessage, $contact);
                    $emailMessage->folder = EmailFolder::getByBoxAndType($emailMessage->folder->emailBox,
                                                                         EmailFolder::TYPE_ARCHIVED);
                    if (!$emailMessage->save())
                    {
                        throw new FailedToSaveModelException();
                    }
                }
            }
        }

        public function actionPopulateContactEmailBeforeCreating($id)
        {
            $postData = PostUtil::getData();
            $contact  = Contact::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserWriteModel($contact);
            $contactPrimaryEmailForm       = new ContactPrimaryEmailForm();
            $contactPrimaryEmailForm->name = strval($contact);
            $this->actionValidatePopulateContactEmailBeforeCreating($postData, $contactPrimaryEmailForm);
            if (isset($postData[get_class($contactPrimaryEmailForm)]))
            {
                //Process saving the email address and redirecting to create email view
                $contactPrimaryEmailForm->setAttributes($postData[get_class($contactPrimaryEmailForm)]);
                $email                 = new Email;
                $email->emailAddress   = $contactPrimaryEmailForm->emailAddress;
                $contact->primaryEmail = $email;
                $saved = $contact->save();
                if (!$saved)
                {
                    throw new FailedToSaveModelException();
                }
                $this->redirect(array($this->getId() . '/createEmailMessage',
                                      'relatedId'             => $contact->id,
                                      'relatedModelClassName' => 'Contact',
                                      'toAddress'             => $contact->primaryEmail->emailAddress));
                Yii::app()->end(false);
            }
            $contactEditView = new ContactRequiresPrimaryEmailFirstModalView(
                $this->getId(),
                $this->getModule()->getId(),
                $contactPrimaryEmailForm);
            $view = new ModalView($this, $contactEditView);
            Yii::app()->getClientScript()->setToAjaxMode();
            echo $view->render();
        }

        protected function actionValidatePopulateContactEmailBeforeCreating($postData, ContactPrimaryEmailForm $contactForm)
        {
            if (isset($postData['ajax']) && $postData['ajax'] == 'edit-form')
            {
                $contactForm->setAttributes($postData[get_class($contactForm)]);
                if ($contactForm->validate())
                {
                    Yii::app()->end(false);
                }
                else
                {
                    $errorData = array();
                    foreach ($contactForm->getErrors() as $attribute => $errors)
                    {
                            $errorData[ZurmoHtml::activeId($contactForm, $attribute)] = $errors;
                    }
                    echo CJSON::encode($errorData);
                }
                Yii::app()->end(false);
            }
        }

        public function actionCreateEmailMessage($toAddress = null, $relatedId = null, $relatedModelClassName = null)
        {
            $postData         = PostUtil::getData();
            $getData          = GetUtil::getData();
            $personOrAccount  = self::resolvePersonOrAccountFromGet($relatedId, $relatedModelClassName);
            $emailMessage     = new EmailMessage();
            $emailMessageForm = new CreateEmailMessageForm($emailMessage);
            $emailMessageForm->setScenario('createNonDraft');
            $postVariableName = get_class($emailMessageForm);
            if ($toAddress == null && $personOrAccount != null && $personOrAccount->primaryEmail->emailAddress != null)
            {
                $toAddress = $personOrAccount->primaryEmail->emailAddress;
            }
            if (isset($postData[$postVariableName]))
            {
                EmailMessageUtil::resolveEmailMessageFromPostData($postData, $emailMessageForm, Yii::app()->user->userModel);
                $this->actionValidateCreateEmailMessage($postData, $emailMessageForm);
                $this->attemptToSaveModelFromPost($emailMessageForm, null, false);
                ZurmoControllerUtil::updatePermissionsWithDefaultForModelByCurrentUser($emailMessageForm->getModel());
            }
            else
            {
                EmailMessageUtil::resolveSignatureToEmailMessage($emailMessage, Yii::app()->user->userModel);
                EmailMessageUtil::resolvePersonOrAccountToEmailMessage($emailMessage, Yii::app()->user->userModel,
                                                                       $toAddress, $relatedId, $relatedModelClassName);
                $createEmailMessageModalEditView = new CreateEmailMessageModalEditView(
                    $this->getId(),
                    $this->getModule()->getId(),
                    $emailMessageForm);
                $view = new ModalView($this, $createEmailMessageModalEditView);
                Yii::app()->getClientScript()->setToAjaxMode();
                echo $view->render();
            }
        }

        protected function resolvePersonOrAccountFromGet($relatedId = null, $relatedModelClassName = null)
        {
            $personOrAccount = null;
            if ($relatedId != null && $relatedModelClassName != null)
            {
                $personOrAccount = $relatedModelClassName::getById((int)$relatedId);
                //Only attempt to populate email if the user has write permissions
                if ($relatedModelClassName == 'Contact' &&
                   $personOrAccount->primaryEmail->emailAddress == null &&
                   ControllerSecurityUtil::doesCurrentUserHavePermissionOnSecurableItem($personOrAccount, Permission::WRITE))
                {
                    $this->redirect(array($this->getId() . '/populateContactEmailBeforeCreating',
                                          'id' => $personOrAccount->id));
                    Yii::app()->end(false);
                }
            }
            return $personOrAccount;
        }

        protected function actionValidateCreateEmailMessage($postData, CreateEmailMessageForm $emailMessageForm)
        {
            if (isset($postData['ajax']) && $postData['ajax'] == 'edit-form')
            {
                $emailMessageForm->setAttributes($postData[get_class($emailMessageForm)]);
                if ($emailMessageForm->validate())
                {
                    Yii::app()->end(false);
                }
                else
                {
                    $errorData = ZurmoActiveForm::makeErrorsDataAndResolveForOwnedModelAttributes($emailMessageForm);
                    echo CJSON::encode($errorData);
                }
                Yii::app()->end(false);
            }
        }

        /**
         * Given a partial name or e-mail address, search for all Users, Leads or Contacts
         * JSON encode the resulting array of contacts.
         */
        public function actionAutoCompleteForMultiSelectAutoComplete($term, $autoCompleteOptions = null)
        {
            $pageSize               = Yii::app()->pagination->resolveActiveForCurrentUserByType(
                                                'autoCompleteListPageSize', get_class($this->getModule()));
            $usersByFullName        = UserSearch::getUsersByPartialFullName($term, $pageSize, $autoCompleteOptions);
            $usersByEmailAddress    = UserSearch::getUsersByEmailAddress($term, 'contains', true, $autoCompleteOptions);
            $contacts               = ContactSearch::getContactsByPartialFullNameOrAnyEmailAddress($term, $pageSize, null, 'contains', $autoCompleteOptions);
            $autoCompleteResults    = array();
            foreach ($usersByEmailAddress as $user)
            {
                $autoCompleteResults[] = array(
                    'id'   => strval($user->primaryEmail),
                    'name' => strval($user) . ' (' . $user->primaryEmail . ')',
                );
            }
            foreach ($usersByFullName as $user)
            {
                $autoCompleteResults[] = array(
                    'id'   => strval($user->primaryEmail),
                    'name' => strval($user) . ' (' . $user->primaryEmail . ')',
                );
            }
            foreach ($contacts as $contact)
            {
                $autoCompleteResults[] = array(
                    'id'   => strval($contact->primaryEmail),
                    'name' => strval($contact) . ' (' . $contact->primaryEmail . ')',
                );
            }
            $emailValidator = new CEmailValidator();
            if (count($autoCompleteResults) == 0 && $emailValidator->validateValue($term))
            {
                $autoCompleteResults[] = array('id' => $term, 'name' => $term);
            }
            echo CJSON::encode($autoCompleteResults);
        }

        protected static function makeSelectForm($userCanAccessLeads, $userCanAccessContacts)
        {
            if ($userCanAccessLeads && $userCanAccessContacts)
            {
                $selectForm = new AnyContactSelectForm();
            }
            elseif (!$userCanAccessLeads && $userCanAccessContacts)
            {
                $selectForm = new ContactSelectForm();
            }
            else
            {
                $selectForm = new LeadSelectForm();
            }
            return $selectForm;
        }

        public function actionDelete($id, $redirectUrl = null, $redirect = true)
        {
            $emailMessage = EmailMessage::getById(intval($id));
            if ($redirectUrl == null)
            {
                $redirectUrl = array('/home/default');
            }
            ControllerSecurityUtil::resolveAccessCanCurrentUserDeleteModel($emailMessage);
            $emailMessage->delete();
            if ($redirect)
            {
                $this->redirect($redirectUrl);
            }
        }

        protected static function getZurmoControllerUtil()
        {
            return new FileZurmoControllerUtil();
        }
    }
?>