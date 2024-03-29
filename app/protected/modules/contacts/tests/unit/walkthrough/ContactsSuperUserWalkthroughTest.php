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
     * Contacts Module Walkthrough.
     * Walkthrough for the super user of all possible controller actions.
     * Since this is a super user, he should have access to all controller actions
     * without any exceptions being thrown.
     */
    class ContactsSuperUserWalkthroughTest extends ZurmoWalkthroughBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;

            //Setup test data owned by the super user.
            $account = AccountTestHelper::createAccountByNameForOwner('superAccount', $super);
            AccountTestHelper::createAccountByNameForOwner           ('superAccount2', $super);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact2', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact3', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact4', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact5', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact6', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact7', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact8', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact9', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact10', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact11', $super, $account);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact12', $super, $account);
            OpportunityTestHelper::createOpportunityStagesIfDoesNotExist     ();
            OpportunityTestHelper::createOpportunityWithAccountByNameForOwner('superOpp', $super, $account);
            //Setup default dashboard.
            Dashboard::getByLayoutIdAndUser                          (Dashboard::DEFAULT_USER_LAYOUT_ID, $super);
            //Make contact DetailsAndRelations portlets
        }

        public function testSuperUserAllDefaultControllerActions()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');

            //Test all default controller actions that do not require any POST/GET variables to be passed.
            //This does not include portlet controller actions.
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default');
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/index');
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/create');

            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/list');
            $this->assertFalse(strpos($content, 'anyMixedAttributes') === false);
            //Test the search or paging of the listview.
            Yii::app()->clientScript->reset(); //to make sure old js doesn't make it to the UI
            $this->setGetArray(array('ajax' => 'list-view'));
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/list');
            $this->assertTrue(strpos($content, 'anyMixedAttributes') === false);
            $this->resetGetArray();

            //Default Controller actions requiring some sort of parameter via POST or GET
            //Load Model Edit Views
            $contacts = Contact::getAll();
            $this->assertEquals(12, count($contacts));
            $superContactId     = self::getModelIdByModelNameAndName ('Contact', 'superContact superContactson');
            $superContactId2    = self::getModelIdByModelNameAndName('Contact', 'superContact2 superContact2son');
            $superContactId3    = self::getModelIdByModelNameAndName('Contact', 'superContact3 superContact3son');
            $superContactId4    = self::getModelIdByModelNameAndName('Contact', 'superContact4 superContact4son');
            $superContactId5    = self::getModelIdByModelNameAndName ('Contact', 'superContact5 superContact5son');
            $superContactId6    = self::getModelIdByModelNameAndName('Contact', 'superContact6 superContact6son');
            $superContactId7    = self::getModelIdByModelNameAndName('Contact', 'superContact7 superContact7son');
            $superContactId8    = self::getModelIdByModelNameAndName('Contact', 'superContact8 superContact8son');
            $superContactId9    = self::getModelIdByModelNameAndName ('Contact', 'superContact9 superContact9son');
            $superContactId10   = self::getModelIdByModelNameAndName('Contact', 'superContact10 superContact10son');
            $superContactId11   = self::getModelIdByModelNameAndName('Contact', 'superContact11 superContact11son');
            $superContactId12   = self::getModelIdByModelNameAndName('Contact', 'superContact12 superContact12son');
            $superOpportunityId = self::getModelIdByModelNameAndName ('Opportunity', 'superOpp');
            $this->setGetArray(array('id' => $superContactId));
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/edit');
            //Save contact.
            $superContact = Contact::getById($superContactId);
            $this->assertEquals(null, $superContact->officePhone);
            $this->setPostArray(array('Contact' => array('officePhone' => '456765421')));
            $this->runControllerWithRedirectExceptionAndGetContent('contacts/default/edit');
            $superContact = Contact::getById($superContactId);
            $this->assertEquals('456765421', $superContact->officePhone);
            //Test having a failed validation on the contact during save.
            $this->setGetArray (array('id'      => $superContactId));
            $this->setPostArray(array('Contact' => array('lastName' => '')));
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/edit');
            $this->assertFalse(strpos($content, 'Name cannot be blank') === false);

            //Load Model Detail Views
            $this->setGetArray(array('id' => $superContactId));
            $this->resetPostArray();
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/details');

            //Load Model MassEdit Views.
            //MassEdit view for single selected ids
            $this->setGetArray(array('selectedIds' => '4,5,6,7,8,9', 'selectAll' => '')); // Not Coding Standard
            $this->resetPostArray();
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massEdit');
            $this->assertFalse(strpos($content, '<strong>6</strong>&#160;records selected for updating') === false);

            //MassEdit view for all result selected ids
            $this->setGetArray(array('selectAll' => '1'));
            $this->resetPostArray();
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massEdit');
            $this->assertFalse(strpos($content, '<strong>12</strong>&#160;records selected for updating') === false);

            //save Model MassEdit for selected Ids
            //Test that the 4 contacts do not have the office phone number we are populating them with.
            $contact1 = Contact::getById($superContactId);
            $contact2 = Contact::getById($superContactId2);
            $contact3 = Contact::getById($superContactId3);
            $contact4 = Contact::getById($superContactId4);
            $this->assertNotEquals('7788', $contact1->officePhone);
            $this->assertNotEquals('7788', $contact2->officePhone);
            $this->assertNotEquals('7788', $contact3->officePhone);
            $this->assertNotEquals('7788', $contact4->officePhone);
            $this->setGetArray(array(
                'selectedIds'  => $superContactId . ',' . $superContactId2, // Not Coding Standard
                'selectAll'    => '',
                'Contact_page' => 1));
            $this->setPostArray(array(
                'Contact'      => array('officePhone' => '7788'),
                'MassEdit'     => array('officePhone' => 1)
            ));
            $this->runControllerWithRedirectExceptionAndGetContent('contacts/default/massEdit');
            //Test that the 2 contacts have the new office phone number and the other contacts do not.
            $contact1  = Contact::getById($superContactId);
            $contact2  = Contact::getById($superContactId2);
            $contact3  = Contact::getById($superContactId3);
            $contact4  = Contact::getById($superContactId4);
            $contact5  = Contact::getById($superContactId5);
            $contact6  = Contact::getById($superContactId6);
            $contact7  = Contact::getById($superContactId7);
            $contact8  = Contact::getById($superContactId8);
            $contact9  = Contact::getById($superContactId9);
            $contact10 = Contact::getById($superContactId10);
            $contact11 = Contact::getById($superContactId11);
            $contact12 = Contact::getById($superContactId12);
            $this->assertEquals   ('7788', $contact1->officePhone);
            $this->assertEquals   ('7788', $contact2->officePhone);
            $this->assertNotEquals('7788', $contact3->officePhone);
            $this->assertNotEquals('7788', $contact4->officePhone);
            $this->assertNotEquals('7788', $contact5->officePhone);
            $this->assertNotEquals('7788', $contact6->officePhone);
            $this->assertNotEquals('7788', $contact7->officePhone);
            $this->assertNotEquals('7788', $contact8->officePhone);
            $this->assertNotEquals('7788', $contact9->officePhone);
            $this->assertNotEquals('7788', $contact10->officePhone);
            $this->assertNotEquals('7788', $contact11->officePhone);
            $this->assertNotEquals('7788', $contact12->officePhone);
            //save Model MassEdit for entire search result
            $this->setGetArray(array(
                'selectAll'    => '1',
                'Contact_page' => 1));
            $this->setPostArray(array(
                'Contact'      => array('officePhone' => '1234'),
                'MassEdit'     => array('officePhone' => 1)
            ));
            $pageSize = Yii::app()->pagination->getForCurrentUserByType('massEditProgressPageSize');
            $this->assertEquals(5, $pageSize);
            Yii::app()->pagination->setForCurrentUserByType('massEditProgressPageSize', 20);
            $this->runControllerWithRedirectExceptionAndGetContent('contacts/default/massEdit');
            Yii::app()->pagination->setForCurrentUserByType('massEditProgressPageSize', $pageSize);
            //Test that all accounts have the new phone number.
            $contact1 = Contact::getById($superContactId);
            $contact2 = Contact::getById($superContactId2);
            $contact3 = Contact::getById($superContactId3);
            $contact4 = Contact::getById($superContactId4);
            $contact5 = Contact::getById($superContactId5);
            $contact6 = Contact::getById($superContactId6);
            $contact7 = Contact::getById($superContactId7);
            $contact8 = Contact::getById($superContactId8);
            $contact9 = Contact::getById($superContactId9);
            $contact10 = Contact::getById($superContactId10);
            $contact11 = Contact::getById($superContactId11);
            $contact12 = Contact::getById($superContactId12);
            $this->assertEquals   ('1234', $contact1->officePhone);
            $this->assertEquals   ('1234', $contact2->officePhone);
            $this->assertEquals   ('1234', $contact3->officePhone);
            $this->assertEquals   ('1234', $contact4->officePhone);
            $this->assertEquals   ('1234', $contact5->officePhone);
            $this->assertEquals   ('1234', $contact6->officePhone);
            $this->assertEquals   ('1234', $contact7->officePhone);
            $this->assertEquals   ('1234', $contact8->officePhone);
            $this->assertEquals   ('1234', $contact9->officePhone);
            $this->assertEquals   ('1234', $contact10->officePhone);
            $this->assertEquals   ('1234', $contact11->officePhone);
            $this->assertEquals   ('1234', $contact12->officePhone);

            //Run Mass Update using progress save.
            $pageSize = Yii::app()->pagination->getForCurrentUserByType('massEditProgressPageSize');
            $this->assertEquals(5, $pageSize);
            Yii::app()->pagination->setForCurrentUserByType('massEditProgressPageSize', 1);
            //The page size is smaller than the result set, so it should exit.
            $this->runControllerWithExitExceptionAndGetContent('contacts/default/massEdit');
            //save Modal MassEdit using progress load for page 2, 3 and 4.
            $this->setGetArray(array('selectAll' => '1', 'Contact_page' => 2));
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massEditProgressSave');
            $this->assertFalse(strpos($content, '"value":16') === false);
            $this->setGetArray(array('selectAll' => '1', 'Contact_page' => 3));
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massEditProgressSave');
            $this->assertFalse(strpos($content, '"value":25') === false);
            $this->setGetArray(array('selectAll' => '1', 'Contact_page' => 4));
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massEditProgressSave');
            $this->assertFalse(strpos($content, '"value":33') === false);
            //Set page size back to old value.
            Yii::app()->pagination->setForCurrentUserByType('massEditProgressPageSize', $pageSize);

            //Autocomplete for Contact
            $this->setGetArray(array('term' => 'super'));
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/autoComplete');
            $this->setGetArray(array('term' => 'super'));
            $this->runControllerWithNoExceptionsAndGetContent('contacts/variableContactState/autoCompleteAllContacts');
            $this->setGetArray(array('term' => 'super'));
            $this->runControllerWithNoExceptionsAndGetContent('contacts/variableContactState/autoCompleteAllContactsForMultiSelectAutoComplete');

            //actionModalList
            $this->setGetArray(array(
                'modalTransferInformation' => array('sourceIdFieldId' => 'x', 'sourceNameFieldId' => 'y', 'modalId' => 'z')
            ));
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/modalList');

            //actionModalListAllContacts
            $this->setGetArray(array(
                'modalTransferInformation' => array('sourceIdFieldId' => 'x', 'sourceNameFieldId' => 'y', 'modalId' => 'z')
            ));
            $this->runControllerWithNoExceptionsAndGetContent('contacts/variableContactState/modalListAllContacts');

            //actionAuditEventsModalList
            $this->setGetArray(array('id' => $superContactId));
            $this->resetPostArray();
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/auditEventsModalList');

            //Select a related Opportunity for this contact. Go to the select screen.
            $contact1->forget();
            $contact1 = Contact::getById($superContactId);
            $portlets = Portlet::getByLayoutIdAndUserSortedByColumnIdAndPosition(
                                    'ContactDetailsAndRelationsView', $super->id, array());
            $this->assertEquals(2, count($portlets));
            $this->assertEquals(3, count($portlets[1]));
            $opportunity = Opportunity::getById($superOpportunityId);
            $this->assertEquals(0, $contact1->opportunities->count());
            $this->assertEquals(0, $opportunity->contacts->count());
            $this->setGetArray(array(   'portletId'             => $portlets[1][1]->id, //Doesnt matter which portlet we are using
                                        'relationAttributeName' => 'contacts',
                                        'relationModuleId'      => 'contacts',
                                        'relationModelId'       => $superContactId,
                                        'uniqueLayoutId'        => 'ContactDetailsAndRelationsView_' .
                                                                    $portlets[1][1]->id)
            );

            $this->resetPostArray();
            $this->runControllerWithNoExceptionsAndGetContent('opportunities/default/SelectFromRelatedList');
            //Now add an opportunity to a contact via the select from related list action.
            $this->setGetArray(array(   'portletId'             => $portlets[1][1]->id,
                                        'modelId'               => $superOpportunityId,
                                        'relationAttributeName' => 'contacts',
                                        'relationModuleId'      => 'contacts',
                                        'relationModelId'       => $superContactId,
                                        'uniqueLayoutId'        => 'ContactDetailsAndRelationsView_' .
                                                                    $portlets[1][1]->id)
            );
            $this->resetPostArray();
            $this->runControllerWithRedirectExceptionAndGetContent('opportunities/defaultPortlet/SelectFromRelatedListSave');
            //Run forget in order to refresh the contact and opportunity showing the new relation
            $contact1->forget();
            $opportunity->forget();
            $contact     = Contact::getById($superContactId);
            $opportunity = Opportunity::getById($superOpportunityId);
            $this->assertEquals(1,                $opportunity->contacts->count());
            $this->assertEquals($contact,         $opportunity->contacts[0]);
            $this->assertEquals(1,                $contact->opportunities->count());
            $this->assertEquals($opportunity->id, $contact->opportunities[0]->id);
        }

        /**
         * @depends testSuperUserAllDefaultControllerActions
         */
        public function testSuperUserDefaultPortletControllerActions()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
            $superContactId2 = self::getModelIdByModelNameAndName('Contact', 'superContact2 superContact2son');

            //Save a layout change. Collapse all portlets in the Contact Details View.
            //At this point portlets for this view should be created because we have already loaded the 'details' page in a request above.
            $portlets = Portlet::getByLayoutIdAndUserSortedByColumnIdAndPosition(
                                    'ContactDetailsAndRelationsView', $super->id, array());
            $this->assertEquals (3, count($portlets[1]));
            $this->assertFalse  (array_key_exists(3, $portlets) );
            $portletPostData = array();
            $portletCount = 0;
            foreach ($portlets as $column => $columnPortlets)
            {
                foreach ($columnPortlets as $position => $portlet)
                {
                    $this->assertEquals('0', $portlet->collapsed);
                    $portletPostData['ContactDetailsAndRelationsView_' . $portlet->id] = array(
                        'collapsed' => 'true',
                        'column'    => 0,
                        'id'        => 'ContactDetailsAndRelationsView_' . $portlet->id,
                        'position'  => $portletCount,
                    );
                    $portletCount++;
                }
            }
            //There should have been a total of 2 portlets.
            $this->assertEquals(6, $portletCount);
            $this->resetGetArray();
            $this->setPostArray(array(
                'portletLayoutConfiguration' => array(
                    'portlets' => $portletPostData,
                    'uniqueLayoutId' => 'ContactDetailsAndRelationsView',
                )
            ));
            $this->runControllerWithNoExceptionsAndGetContent('home/defaultPortlet/saveLayout', true);
            //Now test that all the portlets are collapsed and moved to the first column.
            $portlets = Portlet::getByLayoutIdAndUserSortedByColumnIdAndPosition(
                            'ContactDetailsAndRelationsView', $super->id, array());
            $this->assertEquals (6, count($portlets[1])         );
            $this->assertFalse  (array_key_exists(2, $portlets) );
            foreach ($portlets as $column => $columns)
            {
                foreach ($columns as $position => $positionPortlets)
                {
                    $this->assertEquals('1', $positionPortlets->collapsed);
                }
            }
            //Load Details View again to make sure everything is ok after the layout change.
            $this->setGetArray(array('id' => $superContactId2));
            $this->resetPostArray();
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/details');
        }

        /**
         * @depends testSuperUserDefaultPortletControllerActions
         */
        public function testSuperUserDeleteAction()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
            $superContactId4 = self::getModelIdByModelNameAndName ('Contact', 'superContact4 superContact4son');
            //Delete a contact.
            $this->setGetArray(array('id' => $superContactId4));
            $this->resetPostArray();
            $this->runControllerWithRedirectExceptionAndGetContent('contacts/default/delete');
            $contacts = Contact::getAll();
            $this->assertEquals(11, count($contacts));
            try
            {
                Contact::getById($superContactId4);
                $this->fail();
            }
            catch (NotFoundException $e)
            {
                //success
            }
        }

        /**
         * @depends testSuperUserDeleteAction
         */
        public function testSuperUserCreateAction()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');

            //Confirm the starting states exist
            $this->assertEquals(6, count(ContactState::GetAll()));
            $startingState = ContactsUtil::getStartingState();
            //Create a new contact.
            $this->resetGetArray();
            $this->setPostArray(array('Contact' => array(
                                            'firstName'        => 'myNewContact',
                                            'lastName'         => 'myNewContactson',
                                            'officePhone'      => '456765421',
                                            'state'            => array('id' => $startingState->id)
                                            )
                                      )
                                );
            $this->runControllerWithRedirectExceptionAndGetContent('contacts/default/create');
            $contacts = Contact::getByName('myNewContact myNewContactson');
            $this->assertEquals(1, count($contacts));
            $this->assertTrue($contacts[0]->id > 0);
            $this->assertTrue($contacts[0]->owner == $super);
            $this->assertTrue($contacts[0]->state == $startingState);
            $this->assertEquals('456765421', $contacts[0]->officePhone);
            $contacts = Contact::getAll();
            $this->assertEquals(12, count($contacts));

            //todo: test save with account.
        }

        /**
         * @depends testSuperUserCreateAction
         */
        public function testSuperUserCreateFromRelationAction()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
            $startingState = ContactsUtil::getStartingState();
            $contacts      = Contact::getAll();
            $this->assertEquals(12, count($contacts));
            $account       = Account::getByName('superAccount2');
            $account[0]->billingAddress->street1  = 'some street';
            $account[0]->shippingAddress->street1 = 'some street 2';
            $saved = $account[0]->save();
            $this->assertTrue($saved);
            $opportunity   = OpportunityTestHelper::createOpportunityWithAccountByNameForOwner(
                                'superOpp', $super, $account[0]);

            //Create a new contact from a related account.
            $this->setGetArray(array(   'relationAttributeName' => 'account',
                                        'relationModelId'       => $account[0]->id,
                                        'relationModuleId'      => 'accounts',
                                        'redirectUrl'           => 'someRedirect'));
            $this->setPostArray(array('Contact' => array(
                                        'firstName'         => 'another',
                                        'lastName'          => 'anotherson',
                                        'officePhone'       => '456765421',
                                        'state'             => array('id' => $startingState->id))));
            $this->runControllerWithRedirectExceptionAndGetContent('contacts/default/createFromRelation');
            $contacts = Contact::getByName('another anotherson');
            $this->assertEquals(1, count($contacts));
            $this->assertTrue($contacts[0]->id > 0);
            $this->assertTrue($contacts[0]->owner   == $super);
            $this->assertTrue($contacts[0]->account == $account[0]);
            $this->assertTrue($contacts[0]->state   == $startingState);
            $this->assertEquals('some street',   $contacts[0]->primaryAddress->street1);
            $this->assertEquals('some street 2', $contacts[0]->secondaryAddress->street1);
            $this->assertEquals('some street',   $contacts[0]->account->billingAddress->street1);
            $this->assertEquals('some street 2', $contacts[0]->account->shippingAddress->street1);
            $this->assertEquals('456765421', $contacts[0]->officePhone);
            $contacts = Contact::getAll();
            $this->assertEquals(13, count($contacts));

            //Create a new contact from a related opportunity
            $this->setGetArray(array(   'relationAttributeName' => 'opportunities',
                                        'relationModelId'       => $opportunity->id,
                                        'relationModuleId'      => 'opportunities',
                                        'redirectUrl'           => 'someRedirect'));
            $this->setPostArray(array('Contact' => array(
                                        'firstName'         => 'bnother',
                                        'lastName'          => 'bnotherson',
                                        'officePhone'       => '456765421',
                                        'state'             => array('id' => $startingState->id))));
            $this->runControllerWithRedirectExceptionAndGetContent('contacts/default/createFromRelation');
            $contacts = Contact::getByName('bnother bnotherson');
            $this->assertEquals(1, count($contacts));
            $this->assertTrue($contacts[0]->id > 0);
            $this->assertTrue($contacts[0]->owner   == $super);
            $this->assertEquals(1, $contacts[0]->opportunities->count());
            $this->assertTrue($contacts[0]->opportunities[0] == $opportunity);
            $this->assertTrue($contacts[0]->state   == $startingState);
            $this->assertEquals('456765421', $contacts[0]->officePhone);
            $contacts = Contact::getAll();
            $this->assertEquals(14, count($contacts));

            //todo: test save with account.
        }

        /**
         * @depends testSuperUserCreateFromRelationAction
         */
        public function testSuperUserCopyAction()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');

            $contacts = Contact::getByName('myNewContact myNewContactson');
            $this->assertCount(1, $contacts);

            $postArray = array(
                'Contact' => array(
                    'firstName' => 'myNewContact',
                    'lastName'  => 'myNewContactson',
                    'jobTitle'  => 'job title',
                    'account'   => array(
                            'name'  => 'Linked account',
                    ),
                    'department'    => 'Some department',
                    'officePhone'   => '456765421',
                    'mobilePhone'   => '958462315',
                    'officeFax'     => '123456789',
                    'primaryEmail'  => array(
                            'emailAddress' => 'a@a.com',
                    ),
                    'secondaryEmail' => array(
                            'emailAddress' => 'b@b.com',
                    ),
                    'primaryAddress' => array(
                            'street1'    => 'Street1',
                            'street2'    => 'Street2',
                            'city'       => 'City',
                            'state'      => 'State',
                            'postalCode' => '12345',
                            'country'    => 'Country',
                    ),
                    'secondaryAddress' => array(
                            'street1'    => 'Street1',
                            'street2'    => 'Street2',
                            'city'       => 'City',
                            'state'      => 'State',
                            'postalCode' => '12345',
                            'country'    => 'Country',
                    ),
                    'description' => 'some description',
                )
            );

            $this->updateModelValuesFromPostArray($contacts[0], $postArray);
            $this->assertModelHasValuesFromPostArray($contacts[0], $postArray);

            $this->assertTrue($contacts[0]->save());
            $this->assertTrue(
                $this->checkCopyActionResponseAttributeValuesFromPostArray($contacts[0], $postArray)
            );

            $postArray['Contact']['firstName']  = 'myClonedContact';
            $postArray['Contact']['lastName']   = 'myClonedContactson';
            $postArray['Contact']['state']      = array('id' => LeadsUtil::getStartingState()->id);
            $this->setGetArray(array('id' => $contacts[0]->id));
            $this->setPostArray($postArray);
            $this->runControllerWithRedirectExceptionAndGetUrl('contacts/default/copy');

            $contacts = Contact::getByName('myClonedContact myClonedContactson');
            $this->assertCount(1, $contacts);
            $this->assertTrue($contacts[0]->owner->isSame($super));

            unset($postArray['Contact']['state']);
            $this->assertModelHasValuesFromPostArray($contacts[0], $postArray);

            $contacts = Contact::getAll();
            $this->assertCount(15, $contacts);

            $contacts = Contact::getByName('myClonedContact myClonedContactson');
            $this->assertCount(1, $contacts);
            $this->assertTrue($contacts[0]->delete());
        }

        /**
         * @deletes selected contacts.
         */
        public function testMassDeleteActionsForSelectedIds()
        {
            $super       = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
            $contacts    = Contact::getAll();
            $this->assertEquals(14, count($contacts));
            $superContactId     = self::getModelIdByModelNameAndName('Contact', 'superContact');
            $superContactId2    = self::getModelIdByModelNameAndName('Contact', 'superContact2 superContact2son');
            $superContactId3    = self::getModelIdByModelNameAndName('Contact', 'superContact3 superContact3son');
            $superContactId4    = self::getModelIdByModelNameAndName('Contact', 'myNewContact myNewContactson');
            $superContactId5    = self::getModelIdByModelNameAndName('Contact', 'superContact5 superContact5son');
            $superContactId6    = self::getModelIdByModelNameAndName('Contact', 'superContact6 superContact6son');
            $superContactId7    = self::getModelIdByModelNameAndName('Contact', 'superContact7 superContact7son');
            $superContactId8    = self::getModelIdByModelNameAndName('Contact', 'superContact8 superContact8son');
            $superContactId9    = self::getModelIdByModelNameAndName('Contact', 'superContact9 superContact9son');
            $superContactId10   = self::getModelIdByModelNameAndName('Contact', 'superContact10 superContact10son');
            $superContactId11   = self::getModelIdByModelNameAndName('Contact', 'superContact11 superContact11son');
            $superContactId12   = self::getModelIdByModelNameAndName('Contact', 'superContact12 superContact12son');
            $superContactId13   = self::getModelIdByModelNameAndName('Contact', 'another anotherson');
            $superContactId14   = self::getModelIdByModelNameAndName('Contact', 'bnother bnotherson');
            //Load Model MassDelete Views.
            //MassDelete view for single selected ids
            $this->setGetArray(array('selectedIds' => '5,6,7,8,9', 'selectAll' => '', ));  // Not Coding Standard
            $this->resetPostArray();
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massDelete');
            $this->assertFalse(strpos($content, '<strong>5</strong>&#160;Contacts selected for removal') === false);

             //MassDelete view for all result selected ids
            $this->setGetArray(array('selectAll' => '1'));
            $this->resetPostArray();
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massDelete');
            $this->assertFalse(strpos($content, '<strong>14</strong>&#160;Contacts selected for removal') === false);

            //MassDelete for selected Record Count
            $contacts = contact::getAll();
            $this->assertEquals(14, count($contacts));

            //MassDelete for selected ids for paged scenario
            $contact1 = contact::getById($superContactId);
            $contact2 = contact::getById($superContactId2);
            $contact3 = contact::getById($superContactId3);
            $contact4 = contact::getById($superContactId4);
            $contact5 = contact::getById($superContactId5);
            $contact6 = contact::getById($superContactId6);
            $contact7 = contact::getById($superContactId7);

            $pageSize = Yii::app()->pagination->getForCurrentUserByType('massDeleteProgressPageSize');
            $this->assertEquals(5, $pageSize);
            //MassDelete for selected ids for page 1
            $this->setGetArray(array(
                'selectedIds'  => $superContactId . ',' . $superContactId2 . ',' .  // Not Coding Standard
                                  $superContactId3 . ',' . $superContactId4 . ',' . // Not Coding Standard
                                  $superContactId5 . ',' . $superContactId6 . ',' . // Not Coding Standard
                                  $superContactId7,
                'selectAll'    => '',
                'massDelete'   => '',
                'Contact_page' => 1));
            $this->setPostArray(array('selectedRecordCount' => 7));
            $this->runControllerWithExitExceptionAndGetContent('contacts/default/massDelete');

            //MassDelete for selected Record Count
            $contacts = Contact::getAll();
            $this->assertEquals(9, count($contacts));

            //MassDelete for selected ids for page 2
            $this->setGetArray(array(
                'selectedIds'  => $superContactId . ',' . $superContactId2 . ',' .  // Not Coding Standard
                                  $superContactId3 . ',' . $superContactId4 . ',' . // Not Coding Standard
                                  $superContactId5 . ',' . $superContactId6 . ',' . // Not Coding Standard
                                  $superContactId7,
                'selectAll'    => '',
                'massDelete'   => '',
                'Contact_page' => 2));
            $this->setPostArray(array('selectedRecordCount' => 7));
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massDeleteProgress');

            //MassDelete for selected Record Count
            $contacts = Contact::getAll();
            $this->assertEquals(7, count($contacts));
        }

         /**
         *Test Bug with mass delete and multiple pages when using select all
          * @depends testMassDeleteActionsForSelectedIds
         */
        public function testMassDeletePagesProperlyAndRemovesAllSelected()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');

            //MassDelete for selected Record Count
            $contacts = contact::getAll();
            $this->assertEquals(7, count($contacts));

            //save Model MassDelete for entire search result
            $this->setGetArray(array(
                'selectAll' => '1',           // Not Coding Standard
                'Contact_page' => 1));
            $this->setPostArray(array('selectedRecordCount' => 7));
            //Run Mass Delete using progress save for page1.
            $pageSize = Yii::app()->pagination->getForCurrentUserByType('massDeleteProgressPageSize');
            $this->assertEquals(5, $pageSize);
            $this->runControllerWithExitExceptionAndGetContent('contacts/default/massDelete');

            //check for previous mass delete progress
            $contacts = contact::getAll();
            $this->assertEquals(2, count($contacts));

            $this->setGetArray(array(
                'selectAll' => '1',           // Not Coding Standard
                'Contact_page' => 2));
            $this->setPostArray(array('selectedRecordCount' => 7));
            //Run Mass Delete using progress save for page2.
            $pageSize = Yii::app()->pagination->getForCurrentUserByType('massDeleteProgressPageSize');
            $this->assertEquals(5, $pageSize);
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massDeleteProgress');

            //calculating contact's count
            $contacts = contact::getAll();
            $this->assertEquals(0, count($contacts));
        }

        /**
         * @depends testMassDeletePagesProperlyAndRemovesAllSelected
         */
        public function testGetAccountAddressesToCopy()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');

            $account       = Account::getByName('superAccount2');
            $this->setGetArray(array('id' => $account[0]->id));
            //Run Mass Delete using progress save for page2.
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/getAccountAddressesToCopy');
            // Begin Not Coding Standard
            $compareContent = '{"billingAddress_city":null,"billingAddress_country":null,"billingAddress_invalid":"0","billingAddre'
                            . 'ss_latitude":null,"billingAddress_longitude":null,"billingAddress_postalCode":null,"billingAddress_street1":'
                            . '"some street","billingAddress_street2":null,"billingAddress_state":null,"shippingAddress_city":null,"shippin'
                            . 'gAddress_country":null,"shippingAddress_invalid":"0","shippingAddress_latitude":null,"shippingAddress_longit'
                            . 'ude":null,"shippingAddress_postalCode":null,"shippingAddress_street1":"some street 2","shippingAddress_stree'
                            . 't2":null,"shippingAddress_state":null}';
            // End Not Coding Standard
            $this->assertEquals($compareContent, $content);
        }

        public function testMassSubscribeActionsForSelectedIds()
        {
            $super          = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
            $contactsCount  = Contact::getCount();

            $superContactId1 = ContactTestHelper::createContactByNameForOwner('superContact1', $super);
            $superContactId2 = ContactTestHelper::createContactByNameForOwner('superContact2', $super);
            $superContactId3 = ContactTestHelper::createContactByNameForOwner('superContact3', $super);
            $superContactId4 = ContactTestHelper::createContactByNameForOwner('superContact4', $super);
            $superContactId5 = ContactTestHelper::createContactByNameForOwner('superContact5', $super);
            $superContactId6 = ContactTestHelper::createContactByNameForOwner('superContact6', $super);
            $superContactId7 = ContactTestHelper::createContactByNameForOwner('superContact7', $super);
            $superContactId8 = ContactTestHelper::createContactByNameForOwner('superContact8', $super);
            $superContactId9 = ContactTestHelper::createContactByNameForOwner('superContact9', $super);

            $marketingList1  = MarketingListTestHelper::createMarketingListByName('marketingList1');
            $marketingList2  = MarketingListTestHelper::createMarketingListByName('marketingList2');

            //Load Model MassSubscribe Views.
            //MassSubscribe view for single selected ids
            $selectedIds = implode(',' , array($superContactId1->id, $superContactId4->id)); // Not Coding Standard
            $this->setGetArray(array('selectedIds' => $selectedIds, 'selectAll' => '', ));
            $this->resetPostArray();
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massSubscribe');
            $this->assertContains('<strong>2</strong>&#160;Contacts selected for subscription', $content);

            //MassSubscribe view for all result selected ids
            $this->setGetArray(array('selectAll' => '1'));
            $this->resetPostArray();
            $content = $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massSubscribe');
            $this->assertContains('<strong>9</strong>&#160;Contacts selected for subscription', $content);

            //MassSubscribe with no marketingList selected

            //MassSubscribe for selected ids for paged scenario
            //MassSubscribe for selected ids for page 1
            $pageSize           = Yii::app()->pagination->getForCurrentUserByType('massEditProgressPageSize');
            $this->assertEquals(5, $pageSize);
            $selectedIds = implode(',' , array($superContactId1->id, $superContactId2->id, $superContactId3->id, // Not Coding Standard
                                              $superContactId5->id, $superContactId6->id, $superContactId8->id,
                                              $superContactId9->id));
            $this->setGetArray(array(
                'selectedIds'   => $selectedIds,
                'selectAll'     => '',
                'massSubscribe' => '',
                'Contact_page'  => 1));
            $this->setPostArray(array('selectedRecordCount' => 7,
                                      'MarketingListMember' => array('marketingList' => array('id' => $marketingList1->id))));
            $this->runControllerWithExitExceptionAndGetContent('contacts/default/massSubscribe');
            $expectedSubscribedCountAfterFirstRequest   = $pageSize;
            $actualSubscribedCountAfterFirstRequest     = MarketingListMember::getCountByMarketingListIdAndUnsubscribed($marketingList1->id, 0);
            $this->assertEquals($expectedSubscribedCountAfterFirstRequest, $actualSubscribedCountAfterFirstRequest);
            $this->assertEquals(0, MarketingListMember::getCountByMarketingListIdAndUnsubscribed($marketingList2->id, 0));
            //MassSubscribe for selected ids for page 2
            $this->setGetArray(array(
                'selectedIds'   => $selectedIds,
                'selectAll'     => '',
                'massSubscribe' => '',
                'Contact_page'  => 2,
                'MarketingListMember' => array('marketingList' => array('id' => $marketingList1->id))));
            $this->setPostArray(array('selectedRecordCount' => 7,
                                      'MarketingListMember' => array('marketingList' => array('id' => $marketingList1->id))));
            $this->runControllerWithNoExceptionsAndGetContent('contacts/default/massSubscribeProgress');
            $expectedSubscribedCountAfterSecondRequest   = $actualSubscribedCountAfterFirstRequest + (7 - $pageSize);
            $actualSubscribedCountAfterSecondRequest     = MarketingListMember::getCountByMarketingListIdAndUnsubscribed($marketingList1->id, 0);
            $this->assertEquals($expectedSubscribedCountAfterSecondRequest, $actualSubscribedCountAfterSecondRequest);
            $this->assertEquals(0, MarketingListMember::getCountByMarketingListIdAndUnsubscribed($marketingList2->id, 0));
        }
    }
?>