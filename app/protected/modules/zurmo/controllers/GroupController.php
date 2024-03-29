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

    class ZurmoGroupController extends ZurmoModuleController
    {
        public static function resolveBreadCrumbActionByGroup(Group $group)
        {
            if (!$group->isEveryone && !$group->isSuperAdministrators)
            {
                return 'edit';
            }
            else
            {
                return 'editPolicies';
            }
        }

        public function resolveModuleClassNameForFilters()
        {
            return 'GroupsModule';
        }

        public function resolveAndGetModuleId()
        {
            return 'groups';
        }

        public function actionIndex()
        {
            $this->actionList();
        }

        public function actionList()
        {
            $title           = Zurmo::t('ZurmoModule', 'Groups');
            $breadCrumbLinks = array(
                 $title,
            );
            $introView = new SecurityIntroView('ZurmoModule');
            $treeView = new GroupsActionBarAndTreeListView(
                $this->getId(),
                $this->getModule()->getId(),
                static::getGroupsOrderedByNonDeletablesFirst(),
                $introView
            );
            $view             = new GroupsPageView(ZurmoDefaultAdminViewUtil::
                                         makeViewWithBreadcrumbsForCurrentUser($this, $treeView, $breadCrumbLinks, 'GroupBreadCrumbView'));
            echo $view->render();
        }

        public function actionDetails($id)
        {
            $group  = Group::getById(intval($id));
            $action = $this->resolveActionToGoToAfterSave($group);
            if (!$group->isEveryone && !$group->isSuperAdministrators)
            {
                $this->redirect(array($this->getId() . '/' . $action, 'id' => $id));
            }
            else
            {
                $this->redirect(array($this->getId() . '/' . $action, 'id' => $id));
            }
        }

        public function actionCreate()
        {
            $title           = Zurmo::t('ZurmoModule', 'Create Group');
            $breadCrumbLinks = array($title);
            $titleBarAndCreateView = new GroupActionBarAndEditView($this->getId(), $this->getModule()->getId(),
                                                                   $this->attemptToSaveModelFromPost(new Group()));
            $view                  = new GroupsPageView(ZurmoDefaultAdminViewUtil::
                                         makeViewWithBreadcrumbsForCurrentUser($this, $titleBarAndCreateView, $breadCrumbLinks, 'GroupBreadCrumbView'));
            echo $view->render();
        }

        public function actionEdit($id)
        {
            $group               = Group::getById(intval($id));
            $title           = Zurmo::t('Core', 'Edit');
            $breadCrumbLinks = array(strval($group) => array('group/' . static::resolveBreadCrumbActionByGroup($group),  'id' => $id), $title);
            $this->resolveCanGroupBeEdited($group);
            $titleBarAndEditView = new GroupActionBarAndEditView($this->getId(),
                                                                 $this->getModule()->getId(),
                                                                 $this->attemptToSaveModelFromPost($group));
            $view                = new GroupsPageView(ZurmoDefaultAdminViewUtil::
                                       makeViewWithBreadcrumbsForCurrentUser($this, $titleBarAndEditView, $breadCrumbLinks, 'GroupBreadCrumbView'));
            echo $view->render();
        }

        public function actionModalList()
        {
            $groupsModalTreeView = new SelectParentGroupModalTreeListView(
                $this->getId(),
                $this->getModule()->getId(),
                $_GET['modalTransferInformation']['sourceModelId'],
                static::getGroupsOrderedByNonDeletablesFirst(false),
                $_GET['modalTransferInformation']['sourceIdFieldId'],
                $_GET['modalTransferInformation']['sourceNameFieldId'],
                $_GET['modalTransferInformation']['modalId']
            );
            Yii::app()->getClientScript()->setToAjaxMode();
            $pageTitle           = Zurmo::t('ZurmoModule', 'Select a Parent Group');
            $view                = new ModalView($this, $groupsModalTreeView);
            echo $view->render();
        }

        public function actionDelete($id)
        {
            $group = Group::GetById(intval($id));
            $group->users->removeAll();
            $group->groups->removeAll();
            $group->save();
            $group->delete();
            unset($group);
            $this->redirect(array($this->getId() . '/index'));
        }

        public function actionEditUserMembership($id)
        {
            $group              = Group::getById(intval($id));
            $title              = Zurmo::t('ZurmoModule', 'User Membership');
            $breadCrumbLinks    = array(strval($group) => array('group/' . static::resolveBreadCrumbActionByGroup($group),  'id' => $id), $title);
            $membershipForm     = GroupUserMembershipFormUtil::makeFormFromGroup($group);
            $postVariableName   = get_class($membershipForm);
            if (isset($_POST[$postVariableName]))
            {
                $castedPostData = GroupUserMembershipFormUtil::typeCastPostData($_POST[$postVariableName]);
                GroupUserMembershipFormUtil::setFormFromCastedPost($membershipForm, $castedPostData);
                if (null != $message = GroupUserMembershipFormUtil::validateMembershipChange($membershipForm, $group))
                {
                    Yii::app()->user->setFlash('notification', $message);
                }
                elseif (!$group->canModifyMemberships())
                {
                    throw new SecurityException();
                }
                elseif (GroupUserMembershipFormUtil::setMembershipFromForm($membershipForm, $group))
                {
                        $this->clearCaches();
                        Yii::app()->user->setFlash('notification',
                            Zurmo::t('ZurmoModule', 'User Membership Saved Successfully.')
                        );
                        $action = $this->resolveActionToGoToAfterSave($group);
                        $this->redirect(array($this->getId() . '/' . $action, 'id' => $group->id));
                        Yii::app()->end(0, false);
                }
            }
            $titleBarAndEditView = new GroupActionBarAndUserMembershipEditView(
                                            $this->getId(),
                                            $this->getModule()->getId(),
                                            $membershipForm,
                                            $group,
                                            $this->getModule()->getPluralCamelCasedName());
            $view                = new GroupsPageView(ZurmoDefaultAdminViewUtil::
                                         makeViewWithBreadcrumbsForCurrentUser($this, $titleBarAndEditView, $breadCrumbLinks, 'GroupBreadCrumbView'));
            echo $view->render();
        }

        public function actionEditModulePermissions($id)
        {
            $group            = Group::getById(intval($id));
            $title           = Zurmo::t('ZurmoModule', 'Record Permissions');
            $breadCrumbLinks = array(strval($group) => array('group/' . static::resolveBreadCrumbActionByGroup($group),  'id' => $id), $title);
            $data             =  PermissionsUtil::getAllModulePermissionsDataByPermitable($group);
            $permissionsForm  = ModulePermissionsFormUtil::makeFormFromPermissionsData($data);
            $postVariableName = get_class($permissionsForm);
            if (isset($_POST[$postVariableName]))
            {
                $this->clearCaches();
                $castedPostData     = ModulePermissionsFormUtil::typeCastPostData(
                                        $_POST[$postVariableName]);
                $readyToSetPostData = ModulePermissionsEditViewUtil::resolveWritePermissionsFromArray(
                                        $castedPostData);
                if (ModulePermissionsFormUtil::setPermissionsFromCastedPost($readyToSetPostData, $group))
                {
                    Yii::app()->user->setFlash('notification',
                        Zurmo::t('ZurmoModule', 'Record Permissions Saved Successfully.')
                    );
                    $action = $this->resolveActionToGoToAfterSave($group);
                    $this->redirect(array($this->getId() . '/' . $action, 'id' => $group->id));
                    Yii::app()->end(0, false);
                }
            }
            $permissionsData     = GroupModulePermissionsDataToEditViewAdapater::resolveData($data);
            $metadata            = ModulePermissionsEditViewUtil::resolveMetadataFromData(
                                        $permissionsData,
                                        ModulePermissionsEditAndDetailsView::getMetadata());
            $titleBarAndEditView = new GroupActionBarAndSecurityEditView(
                                            $this->getId(),
                                            $this->getModule()->getId(),
                                            $permissionsForm,
                                            $group,
                                            $this->getModule()->getPluralCamelCasedName(),
                                            $metadata,
                                            'ModulePermissionsEditAndDetailsView',
                                            'GroupModulePermissionsEditMenu');
            $view                = new GroupsPageView(ZurmoDefaultAdminViewUtil::
                                         makeViewWithBreadcrumbsForCurrentUser($this, $titleBarAndEditView, $breadCrumbLinks, 'GroupBreadCrumbView'));
            echo $view->render();
        }

        public function actionEditRights($id)
        {
            $group              = Group::getById(intval($id));
            $title           = Zurmo::t('ZurmoModule', 'Rights');
            $breadCrumbLinks = array(strval($group) => array('group/' . static::resolveBreadCrumbActionByGroup($group),  'id' => $id), $title);
            $rightsData         = RightsUtil::getAllModuleRightsDataByPermitable($group);
            $rightsForm         = RightsFormUtil::makeFormFromRightsData($rightsData);
            $postVariableName   = get_class($rightsForm);
            if (isset($_POST[$postVariableName]))
            {
                $castedPostData = RightsFormUtil::typeCastPostData($_POST[$postVariableName]);
                if (RightsFormUtil::setRightsFromCastedPost($castedPostData, $group))
                {
                    $this->clearCaches();
                    $group->forget();
                    $group      = Group::getById(intval($id));
                    Yii::app()->user->setFlash('notification', Zurmo::t('ZurmoModule', 'Rights Saved Successfully.'));
                    $action = $this->resolveActionToGoToAfterSave($group);
                    $this->redirect(array($this->getId() . '/' . $action, 'id' => $group->id));
                    Yii::app()->end(0, false);
                }
            }
            $metadata            = RightsEditViewUtil::resolveMetadataFromData(
                                            $rightsForm->data,
                                            RightsEditAndDetailsView::getMetadata());
            $titleBarAndEditView = new GroupActionBarAndSecurityEditView(
                                            $this->getId(),
                                            $this->getModule()->getId(),
                                            $rightsForm,
                                            $group,
                                            $this->getModule()->getPluralCamelCasedName(),
                                            $metadata,
                                            'RightsEditAndDetailsView',
                                            'GroupRightsEditMenu');
            $view                = new GroupsPageView(ZurmoDefaultAdminViewUtil::
                                         makeViewWithBreadcrumbsForCurrentUser($this, $titleBarAndEditView, $breadCrumbLinks, 'GroupBreadCrumbView'));
            echo $view->render();
        }

        public function actionEditPolicies($id)
        {
            $group              = Group::getById(intval($id));
            $title           = Zurmo::t('ZurmoModule', 'Policies');
            $breadCrumbLinks = array(strval($group) => array('group/' . static::resolveBreadCrumbActionByGroup($group),  'id' => $id), $title);
            $data               = PoliciesUtil::getAllModulePoliciesDataByPermitable($group);
            $policiesForm       = PoliciesFormUtil::makeFormFromPoliciesData($data);
            $postVariableName   = get_class($policiesForm);
            if (isset($_POST[$postVariableName]))
            {
                $castedPostData = PoliciesFormUtil::typeCastPostData($_POST[$postVariableName]);
                $policiesForm   = PoliciesFormUtil::loadFormFromCastedPost($policiesForm, $castedPostData);
                if ($policiesForm->validate())
                {
                    if (PoliciesFormUtil::setPoliciesFromCastedPost($castedPostData, $group))
                    {
                        $this->clearCaches();
                        Yii::app()->user->setFlash('notification',
                            Zurmo::t('ZurmoModule', 'Policies Saved Successfully.')
                        );
                        $action = $this->resolveActionToGoToAfterSave($group);
                        $this->redirect(array($this->getId() . '/' . $action, 'id' => $group->id));
                        Yii::app()->end(0, false);
                    }
                }
            }
            $metadata            = PoliciesEditViewUtil::resolveMetadataFromData(
                                        $policiesForm->data,
                                        PoliciesEditAndDetailsView::getMetadata());
            $titleBarAndEditView = new GroupActionBarAndSecurityEditView(
                                        $this->getId(),
                                        $this->getModule()->getId(),
                                        $policiesForm,
                                        $group,
                                        $this->getModule()->getPluralCamelCasedName(),
                                        $metadata,
                                        'PoliciesEditAndDetailsView',
                                        'GroupPoliciesEditMenu');
            $view                = new GroupsPageView(ZurmoDefaultAdminViewUtil::
                                         makeViewWithBreadcrumbsForCurrentUser($this, $titleBarAndEditView, $breadCrumbLinks, 'GroupBreadCrumbView'));
            echo $view->render();
        }

        /**
         * Override to support special scenario of checking for
         * a reserved name.  Cannot use normal validate routine since
         * the _set is blocking the entry of a reserved name and _set is used
         * by setAttributes which comes before validate is called.
         */
        protected function attemptToSaveModelFromPost($model, $redirectUrlParams = null, $redirect = true)
        {
            assert('$redirectUrlParams == null || is_array($redirectUrlParams)');
            $postVariableName = get_class($model);
            if (isset($_POST[$postVariableName]))
            {
                if ($model->isNameNotAReservedName($_POST[$postVariableName]['name']))
                {
                    $model->setAttributes($_POST[$postVariableName]);
                    if ($model->save())
                    {
                        Yii::app()->user->setFlash('notification',
                            Zurmo::t('ZurmoModule', 'Group Saved Successfully.')
                        );
                        if ($redirectUrlParams == null)
                        {
                            $action    = $this->resolveActionToGoToAfterSave($model);
                            $urlParams = array($this->getId() . '/' . $action, 'id' => $model->id);
                        }
                        $this->redirect($urlParams);
                    }
                }
            }
            return $model;
        }

        /**
         * Override to make sure the correct module label is used in the titlebar.
         * @see Controller::makeTitleBarAndEditAndDetailsView()
         */
        protected function makeTitleBarAndEditAndDetailsView($model, $renderType,
                                $titleBarAndEditViewClassName = 'TitleBarAndEditAndDetailsView')
        {
            assert('$model != null');
            assert('$renderType == "Edit"');
            assert('$titleBarAndEditViewClassName != null && is_string($titleBarAndEditViewClassName)');
            return new $titleBarAndEditViewClassName(
                $this->getId(),
                $this->getModule()->getId(),
                $model,
                GroupsModule::getPluralCamelCasedName(),
                $renderType
            );
        }

        protected function resolveCanGroupBeEdited($group)
        {
            if (!$group->isEveryone && !$group->isSuperAdministrators)
            {
                return;
            }
            $messageView = new AccessFailureView();
            $view = new AccessFailurePageView($messageView);
            echo $view->render();
            Yii::app()->end(0, false);
        }

        protected static function getGroupsOrderedByNonDeletablesFirst($includeEveryoneAndSuperAdministratorGroups = true)
        {
            if ($includeEveryoneAndSuperAdministratorGroups)
            {
                $groups = array(Group::getByName(Group::EVERYONE_GROUP_NAME),
                                Group::getByName(Group::SUPER_ADMINISTRATORS_GROUP_NAME));
            }
            else
            {
                $groups = array();
            }
            $where    = Group::getTableName('Group') . ".name NOT IN( '" . Group::EVERYONE_GROUP_NAME . "', '" . Group::SUPER_ADMINISTRATORS_GROUP_NAME . "')";
            $orderBy  = Group::getTableName('Group') . '.name asc';
            return array_merge($groups, Group::getSubset(null, null, null, $where, $orderBy));
        }

        protected function clearCaches()
        {
            PermissionsCache::forgetAll();
            RightsCache::forgetAll();
            PoliciesCache::forgetAll();
        }

        protected function resolveActionToGoToAfterSave(Group $group)
        {
            if (!$group->isEveryone && !$group->isSuperAdministrators)
            {
                return 'edit';
            }
            else
            {
                return 'editPolicies';
            }
        }

        public function actionUsersInGroupModalList($id)
        {
            $model = Group::getById((int)$id);
            ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($model);
            $searchAttributeData = UsersByModelModalListControllerUtil::makeModalSearchAttributeDataByModel($model, 'groups');
            $dataProvider = UsersByModelModalListControllerUtil::makeDataProviderBySearchAttributeData($searchAttributeData);
            Yii::app()->getClientScript()->setToAjaxMode();
            echo UsersByModelModalListControllerUtil::renderList($this, $dataProvider, 'usersInGroupModalList');
        }

        public function actionAutoComplete($term, $autoCompleteOptions = null)
        {
            echo $this->renderAutoCompleteResults(GroupsModule::getPrimaryModelName(), $term, $autoCompleteOptions);
        }
    }
?>