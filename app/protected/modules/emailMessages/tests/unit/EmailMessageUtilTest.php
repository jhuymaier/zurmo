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

    class EmailMessageUtilTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            Yii::app()->user->userModel = User::getByUsername('super');
            ReadPermissionsOptimizationUtil::rebuild();

            SecurityTestHelper::createUsers();

            $billy = User::getByUsername('billy');
            EmailMessageTestHelper::createEmailAccount($billy);
            $billy->setRight('ContactsModule', ContactsModule::RIGHT_ACCESS_CONTACTS);
            $billy->setRight('ContactsModule', ContactsModule::RIGHT_CREATE_CONTACTS);
            $billy->setRight('ContactsModule', ContactsModule::RIGHT_DELETE_CONTACTS);
            assert($billy->save()); // Not Coding Standard

            $contact = ContactTestHelper::createContactByNameForOwner('sally', Yii::app()->user->userModel);
            $contact->primaryEmail = new Email();
            $contact->primaryEmail->emailAddress = 'sally@zurmoland.com';
            $contact->secondaryEmail->emailAddress = 'toMakeSureNoFreeze@works.com';
            $contact->addPermissions($billy, Permission::READ);
            $contact->addPermissions($billy, Permission::WRITE);
            $contact->save();
            $molly = ContactTestHelper::createContactByNameForOwner('molly', User::getByUsername('bobby'));
            $molly->primaryEmail = new Email();
            $molly->primaryEmail->emailAddress = 'molly@zurmoland.com';
            $molly->secondaryEmail->emailAddress = 'toMakeSureNoFreeze@works.zur';
            $contact->save();
            ReadPermissionsOptimizationUtil::securableItemGivenPermissionsForUser($contact, $billy);
        }

        public function testResolveEmailMessageFromPostData()
        {
            $billy                      = User::getByUsername('billy');
            Yii::app()->user->userModel = $billy;
            //Test with no users/person in recipients
            $emailMessage     = new EmailMessage();
            $emailMessageForm = new CreateEmailMessageForm($emailMessage);
            $postVariableName = get_class($emailMessageForm);
            $postData = array($postVariableName => array ('recipientsData' => array('to'  => 'a@zurmo.com,b@zurmo.com', // Not Coding Standard
                                                                          'cc'  => 'c@zurmo.com,d@zurmo.com',           // Not Coding Standard
                                                                          'bcc' => 'e@zurmo.com,f@zurmo.com'),          // Not Coding Standard
                                                    'subject' => 'Test Email From Post',
                                                    'content' => array('htmlContent' => 'This is a test email')
                ));
            $emailMessageForm = EmailMessageUtil::resolveEmailMessageFromPostData($postData, $emailMessageForm, $billy);
            //Message should have 6 recipients 2 of each type
            $this->assertEquals('6', count($emailMessageForm->getModel()->recipients));
            $recipients = $emailMessageForm->getModel()->recipients;
            $this->assertEquals(EmailMessageRecipient::TYPE_TO, $recipients[0]->type);
            $this->assertEquals(EmailMessageRecipient::TYPE_TO, $recipients[1]->type);
            $this->assertEquals(EmailMessageRecipient::TYPE_CC, $recipients[2]->type);
            $this->assertEquals(EmailMessageRecipient::TYPE_CC, $recipients[3]->type);
            $this->assertEquals(EmailMessageRecipient::TYPE_BCC, $recipients[4]->type);
            $this->assertEquals(EmailMessageRecipient::TYPE_BCC, $recipients[5]->type);
            $this->assertEquals('a@zurmo.com', $recipients[0]->toAddress);
            $this->assertEquals('b@zurmo.com', $recipients[1]->toAddress);
            $this->assertEquals('c@zurmo.com', $recipients[2]->toAddress);
            $this->assertEquals('d@zurmo.com', $recipients[3]->toAddress);
            $this->assertEquals('e@zurmo.com', $recipients[4]->toAddress);
            $this->assertEquals('f@zurmo.com', $recipients[5]->toAddress);
            $this->assertEquals('', $recipients[0]->toName);
            $this->assertEquals('', $recipients[1]->toName);
            $this->assertEquals('', $recipients[2]->toName);
            $this->assertEquals('', $recipients[3]->toName);
            $this->assertEquals('', $recipients[4]->toName);
            $this->assertEquals('', $recipients[5]->toName);
            //Recipients are not personOrAccount
            $this->assertCount(0, $recipients[0]->personsOrAccounts);
            $this->assertCount(0, $recipients[1]->personsOrAccounts);
            $this->assertCount(0, $recipients[2]->personsOrAccounts);
            $this->assertCount(0, $recipients[3]->personsOrAccounts);
            $this->assertCount(0, $recipients[4]->personsOrAccounts);
            $this->assertCount(0, $recipients[5]->personsOrAccounts);
            //The message should go to billy's box
            $this->assertEquals(EmailBox::USER_DEFAULT_NAME, $emailMessageForm->folder->emailBox->name);
            //The message should go to the default outbox folder
            $this->assertEquals(EmailFolder::getDefaultOutboxName(), $emailMessageForm->folder->name);
            $this->assertEquals(EmailFolder::TYPE_OUTBOX, $emailMessageForm->folder->type);
            //The sender should be populated with details from the users email account
            $emailAccount = EmailAccount::getByUserAndName($billy);
            $this->assertEquals($emailAccount->fromName, $emailMessageForm->sender->fromName);
            $this->assertEquals($emailAccount->fromAddress, $emailMessageForm->sender->fromAddress);

            //Test with null in cc/bcc
            $emailMessage     = new EmailMessage();
            $emailMessageForm = new CreateEmailMessageForm($emailMessage);
            $postVariableName = get_class($emailMessageForm);
            $postData = array($postVariableName => array ('recipientsData' => array('to'  => 'a@zurmo.com',
                                                                          'cc'  => null,
                                                                          'bcc' => null),
                                                    'subject' => 'Test Email From Post',
                                                    'content' => array('htmlContent' => 'This is a test email')
                ));
            $emailMessageForm = EmailMessageUtil::resolveEmailMessageFromPostData($postData, $emailMessageForm, $billy);
            $this->assertEquals('1', count($emailMessageForm->getModel()->recipients));
            $this->assertEquals(EmailMessageRecipient::TYPE_TO, $emailMessageForm->getModel()->recipients[0]->type);
            $this->assertEquals('a@zurmo.com', $emailMessageForm->getModel()->recipients[0]->toAddress);

            //Test with with contacts in recipients
            $emailMessage     = new EmailMessage();
            $emailMessageForm = new CreateEmailMessageForm($emailMessage);
            $postVariableName = get_class($emailMessageForm);
            $postData = array($postVariableName => array ('recipientsData' => array('to'  => 'sally@zurmoland.com',
                                                                          'cc'  => null,
                                                                          'bcc' => null),
                                                    'subject' => 'Test Email From Post',
                                                    'content' => array('htmlContent' => 'This is a test email')
            ));
            $emailMessageForm = EmailMessageUtil::resolveEmailMessageFromPostData($postData, $emailMessageForm, $billy);
            $this->assertEquals('1', count($emailMessageForm->getModel()->recipients));
            $this->assertEquals(EmailMessageRecipient::TYPE_TO, $emailMessageForm->getModel()->recipients[0]->type);
            $this->assertEquals('sally@zurmoland.com', $emailMessageForm->getModel()->recipients[0]->toAddress);
            $contacts = Contact::getByName('sally sallyson');
            $this->assertTrue($emailMessageForm->getModel()->recipients[0]->personsOrAccounts[0]->isSame($contacts[0]));

            //Test with attachments
            $email = new Email();
            $filesIds = array();
            $fileDocx = ZurmoTestHelper::createFileModel('testNote.txt');
            $filesIds[] = $fileDocx->id;
            $fileTxt = ZurmoTestHelper::createFileModel('testImage.png');
            $filesIds[] = $fileTxt->id;
            $emailMessage     = new EmailMessage();
            $emailMessageForm = new CreateEmailMessageForm($emailMessage);
            $postVariableName = get_class($emailMessageForm);
            $postData = array($postVariableName => array ('recipientsData' => array('to'  => 'a@zurmo.com',
                                                                          'cc'  => null,
                                                                          'bcc' => null),
                                                    'subject' => 'Test Email From Post',
                                                    'content' => array('htmlContent' => 'This is a test email')
                                             ),
                           'filesIds'     => $filesIds,
                );
            $emailMessageForm = EmailMessageUtil::resolveEmailMessageFromPostData($postData, $emailMessageForm, $billy);
            $this->assertEquals(2, count($emailMessageForm->getModel()->files));
        }

        public function testAttachFilesToMessage()
        {
            $billy = User::getByUsername('billy');
            Yii::app()->user->userModel = $billy;
            $filesIds = array();
            $fileTxt = ZurmoTestHelper::createFileModel('testNote.txt');
            $filesIds[] = $fileTxt->id;
            $filePng = ZurmoTestHelper::createFileModel('testImage.png');
            $filesIds[] = $filePng->id;
            $fileZip = ZurmoTestHelper::createFileModel('testZip.zip');
            $filesIds[] = $fileZip->id;
            $filePdf = ZurmoTestHelper::createFileModel('testPDF.pdf');
            $filesIds[] = $filePdf->id;
            $emailMessage = new EmailMessage();
            EmailMessageUtil::attachFilesToMessage($filesIds, $emailMessage);
            $this->assertEquals('4', count($emailMessage->files));
        }

        public function testAttachRecipientsToMessage()
        {
            $billy                      = User::getByUsername('billy');
            Yii::app()->user->userModel = $billy;
            $emailMessage = new EmailMessage();
            //Attach non personOrAccount recipient
            EmailMessageUtil::attachRecipientsToMessage(array('a@zurmo.com', 'b@zurmo.com', 'c@zurmo.com'), $emailMessage, EmailMessageRecipient::TYPE_TO);
            $this->assertCount(3, $emailMessage->recipients);
            $this->assertCount(0, $emailMessage->recipients[0]->personsOrAccounts);
            $this->assertCount(0, $emailMessage->recipients[1]->personsOrAccounts);
            $this->assertCount(0, $emailMessage->recipients[2]->personsOrAccounts);
            $this->assertEquals(EmailMessageRecipient::TYPE_TO, $emailMessage->recipients[0]->type);
            $this->assertEquals(EmailMessageRecipient::TYPE_TO, $emailMessage->recipients[1]->type);
            $this->assertEquals(EmailMessageRecipient::TYPE_TO, $emailMessage->recipients[2]->type);
            //Attach personOrAccount recipient

            EmailMessageUtil::attachRecipientsToMessage(array('sally@zurmoland.com', 'molly@zurmoland.com'), $emailMessage, EmailMessageRecipient::TYPE_BCC);
            $this->assertEquals('5', count($emailMessage->recipients));
            $contacts = Contact::getByName('sally sallyson');
            $this->assertTrue($emailMessage->recipients[3]->personsOrAccounts[0]->isSame($contacts[0]));
            $this->assertEquals(EmailMessageRecipient::TYPE_BCC, $emailMessage->recipients[3]->type);
            //User billy dont have permision to molly contact
            Yii::app()->user->userModel = User::getByUsername('super');
            $contacts = Contact::getByName('molly mollyson');
            $this->assertCount(0, $emailMessage->recipients[4]->personsOrAccounts);
            $this->assertEquals($emailMessage->recipients[4]->toAddress, $contacts[0]->primaryEmail->emailAddress);
            $this->assertEquals(EmailMessageRecipient::TYPE_BCC, $emailMessage->recipients[4]->type);
            //Attach an empty email
            EmailMessageUtil::attachRecipientsToMessage(array(''), $emailMessage, EmailMessageRecipient::TYPE_CC);
            $this->assertEquals('5', count($emailMessage->recipients));
        }

        public function testRenderEmailAddressAsMailToOrModalLinkStringContent()
        {
            $billy   = User::getByUsername('billy');
            Yii::app()->user->userModel = $billy;
            $emailAddress = "a@zurmo.com";
            $account = new Account();
            $content = EmailMessageUtil::renderEmailAddressAsMailToOrModalLinkStringContent($emailAddress, $account);
            $this->assertEquals('<a href="mailto:a@zurmo.com">a@zurmo.com</a>', $content);
            $billy->setRight('EmailMessagesModule', EmailMessagesModule::RIGHT_ACCESS_CONFIGURATION);
            $billy->setRight('EmailMessagesModule', EmailMessagesModule::RIGHT_ACCESS_EMAIL_MESSAGES);
            $billy->setRight('EmailMessagesModule', EmailMessagesModule::RIGHT_CREATE_EMAIL_MESSAGES);
            $billy->save();
            $content = EmailMessageUtil::renderEmailAddressAsMailToOrModalLinkStringContent($emailAddress, $account);
            $this->assertEquals('<a href="mailto:a@zurmo.com">a@zurmo.com</a>', $content);
            //Only if the model is not Account and User as right he can see the email modal link
            $contact = new Contact();
            $content = EmailMessageUtil::renderEmailAddressAsMailToOrModalLinkStringContent($emailAddress, $contact);
            $this->assertContains('href=',                          $content);
            $this->assertContains('toAddress=a%40zurmo.com&amp;',   $content); // Not Coding Standard
        }

        public function testResolveTextContent()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $htmlContent = "<br>A test message.";
            $textContent = '';
            $textContent = EmailMessageUtil::resolveTextContent($htmlContent, $textContent);
            $this->assertEquals("\nA test message.", $textContent);
            $htmlContent = "<p>A new test message.</p>";
            $textContent = EmailMessageUtil::resolveTextContent($htmlContent, $textContent);
            $this->assertEquals("\nA test message.", $textContent);
            $htmlContent = "<p>A test message.</p>";
            $textContent = '';
            $textContent = EmailMessageUtil::resolveTextContent($htmlContent, $textContent);
            $this->assertEquals("\n\nA test message.", $textContent);
            $htmlContent = "<u>A test</u> <b>message</b>.";
            $textContent = '';
            $textContent = EmailMessageUtil::resolveTextContent($htmlContent, $textContent);
            $this->assertEquals("A test message.", $textContent);
            $htmlContent = "<u><p>A test</p></u> <b>message</b>.";
            $textContent = '';
            $textContent = EmailMessageUtil::resolveTextContent($htmlContent, $textContent);
            $this->assertEquals("\n\nA test message.", $textContent);
            $htmlContent = "<br /><p>A test</p> <p>message</p>.";
            $textContent = '';
            $textContent = EmailMessageUtil::resolveTextContent($htmlContent, $textContent);
            $this->assertEquals("\n\n\nA test \n\nmessage.", $textContent);
        }
    }
?>