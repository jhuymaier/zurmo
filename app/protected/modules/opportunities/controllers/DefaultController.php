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

    class OpportunitiesDefaultController extends ZurmoModuleController
    {
        public function filters()
        {
            $modelClassName   = $this->getModule()->getPrimaryModelName();
            $viewClassName    = $modelClassName . 'EditAndDetailsView';
            return array_merge(parent::filters(),
                array(
                    array(
                        ZurmoBaseController::REQUIRED_ATTRIBUTES_FILTER_PATH . ' + create, createFromRelation, edit',
                        'moduleClassName' => get_class($this->getModule()),
                        'viewClassName'   => $viewClassName,
                   ),
                    array(
                        ZurmoModuleController::ZERO_MODELS_CHECK_FILTER_PATH . ' + list, index',
                        'controller' => $this,
                   ),
               )
            );
        }

        public function actionList()
        {
            $pageSize    = Yii::app()->pagination->resolveActiveForCurrentUserByType(
                           'listPageSize', get_class($this->getModule()));
            $opportunity = new Opportunity(false);
            $searchForm  = new OpportunitiesSearchForm($opportunity);
            $searchForm->setKanbanBoard(new KanbanBoard($opportunity, 'stage'));
            $listAttributesSelector = new ListAttributesSelector('OpportunitiesListView', get_class($this->getModule()));
            $searchForm->setListAttributesSelector($listAttributesSelector);
            $dataProvider = $this->resolveSearchDataProvider(
                $searchForm,
                $pageSize,
                null,
                'OpportunitiesSearchView'
            );
            if (isset($_GET['ajax']) && $_GET['ajax'] == 'list-view')
            {
                $mixedView = $this->makeListView(
                    $searchForm,
                    $dataProvider
                );
                $view = new OpportunitiesPageView($mixedView);
            }
            else
            {
                $activeActionElementType = $this->resolveActiveElementTypeForKanbanBoard($searchForm);
                $mixedView = $this->makeActionBarSearchAndListView($searchForm, $dataProvider,
                             'OpportunitiesSecuredActionBarForSearchAndListView', null, $activeActionElementType);
                $view      = new OpportunitiesPageView(ZurmoDefaultViewUtil::
                             makeStandardViewForCurrentUser($this, $mixedView));
            }
            echo $view->render();
        }

        public function actionDetails($id, $kanbanBoard = false)
        {
            $opportunity = static::getModelAndCatchNotFoundAndDisplayError('Opportunity', intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($opportunity);
            AuditEvent::logAuditEvent('ZurmoModule', ZurmoModule::AUDIT_EVENT_ITEM_VIEWED, array(strval($opportunity), 'OpportunitiesModule'), $opportunity);
            if (KanbanUtil::isKanbanRequest() === false)
            {
                $breadCrumbView          = StickySearchUtil::resolveBreadCrumbViewForDetailsControllerAction($this, 'OpportunitiesSearchView', $opportunity);
                $detailsAndRelationsView = $this->makeDetailsAndRelationsView($opportunity, 'OpportunitiesModule',
                                                                          'OpportunityDetailsAndRelationsView',
                                                                          Yii::app()->request->getRequestUri(), $breadCrumbView);
                $view = new OpportunitiesPageView(ZurmoDefaultViewUtil::
                                             makeStandardViewForCurrentUser($this, $detailsAndRelationsView));
            }
            else
            {
                $view = TasksUtil::resolveTaskKanbanViewForRelation($opportunity, $this->getModule()->getId(), $this,
                                                                        'TasksForOpportunityKanbanView', 'OpportunitiesPageView');
            }
            echo $view->render();
        }

        public function actionCreate()
        {
            $this->actionCreateByModel(new Opportunity());
        }

        public function actionCreateFromRelation($relationAttributeName, $relationModelId, $relationModuleId, $redirectUrl)
        {
            $opportunity = $this->resolveNewModelByRelationInformation( new Opportunity(),
                                                                                $relationAttributeName,
                                                                                (int)$relationModelId,
                                                                                $relationModuleId);
            $this->actionCreateByModel($opportunity, $redirectUrl);
        }

        protected function actionCreateByModel(Opportunity $opportunity, $redirectUrl = null)
        {
            $titleBarAndEditView = $this->makeEditAndDetailsView(
                                            $this->attemptToSaveModelFromPost($opportunity, $redirectUrl), 'Edit');
            $view = new OpportunitiesPageView(ZurmoDefaultViewUtil::
                                         makeStandardViewForCurrentUser($this, $titleBarAndEditView));
            echo $view->render();
        }

        public function actionEdit($id, $redirectUrl = null)
        {
            $opportunity = Opportunity::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserWriteModel($opportunity);
            $this->processEdit($opportunity, $redirectUrl);
        }

        public function actionCopy($id)
        {
            $copyToOpportunity  = new Opportunity();
            $postVariableName   = get_class($copyToOpportunity);
            if (!isset($_POST[$postVariableName]))
            {
                $opportunity    = Opportunity::getById((int)$id);
                ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($opportunity);
                ZurmoCopyModelUtil::copy($opportunity, $copyToOpportunity);
            }
            $this->processEdit($copyToOpportunity);
        }

        protected function processEdit(Opportunity $opportunity, $redirectUrl = null)
        {
            $view = new OpportunitiesPageView(ZurmoDefaultViewUtil::
                                         makeStandardViewForCurrentUser($this,
                                             $this->makeEditAndDetailsView(
                                                        $this->attemptToSaveModelFromPost($opportunity, $redirectUrl),
                                                        'Edit')));
            echo $view->render();
        }

        /**
         * Action for displaying a mass edit form and also action when that form is first submitted.
         * When the form is submitted, in the event that the quantity of models to update is greater
         * than the pageSize, then once the pageSize quantity has been reached, the user will be
         * redirected to the makeMassEditProgressView.
         * In the mass edit progress view, a javascript refresh will take place that will call a refresh
         * action, usually massEditProgressSave.
         * If there is no need for a progress view, then a flash message will be added and the user will
         * be redirected to the list view for the model.  A flash message will appear providing information
         * on the updated records.
         * @see Controler->makeMassEditProgressView
         * @see Controller->processMassEdit
         * @see
         */
        public function actionMassEdit()
        {
            $pageSize = Yii::app()->pagination->resolveActiveForCurrentUserByType(
                            'massEditProgressPageSize');
            $opportunity = new Opportunity(false);
            $activeAttributes = $this->resolveActiveAttributesFromMassEditPost();
            $dataProvider = $this->getDataProviderByResolvingSelectAllFromGet(
                new OpportunitiesSearchForm($opportunity),
                $pageSize,
                Yii::app()->user->userModel->id,
                null,
                'OpportunitiesSearchView');
            $selectedRecordCount = static::getSelectedRecordCountByResolvingSelectAllFromGet($dataProvider);
            $opportunity = $this->processMassEdit(
                $pageSize,
                $activeAttributes,
                $selectedRecordCount,
                'OpportunitiesPageView',
                $opportunity,
                OpportunitiesModule::getModuleLabelByTypeAndLanguage('Plural'),
                $dataProvider
            );
            $massEditView = $this->makeMassEditView(
                $opportunity,
                $activeAttributes,
                $selectedRecordCount,
                OpportunitiesModule::getModuleLabelByTypeAndLanguage('Plural')
            );
            $view = new OpportunitiesPageView(ZurmoDefaultViewUtil::
                                         makeStandardViewForCurrentUser($this, $massEditView));
            echo $view->render();
        }

        /**
         * Action called in the event that the mass edit quantity is larger than the pageSize.
         * This action is called after the pageSize quantity has been updated and continues to be
         * called until the mass edit action is complete.  For example, if there are 20 records to update
         * and the pageSize is 5, then this action will be called 3 times.  The first 5 are updated when
         * the actionMassEdit is called upon the initial form submission.
         */
        public function actionMassEditProgressSave()
        {
            $pageSize = Yii::app()->pagination->resolveActiveForCurrentUserByType(
                            'massEditProgressPageSize');
            $opportunity = new Opportunity(false);
            $dataProvider = $this->getDataProviderByResolvingSelectAllFromGet(
                new OpportunitiesSearchForm($opportunity),
                $pageSize,
                Yii::app()->user->userModel->id,
                null,
                'OpportunitiesSearchView'
            );
            $this->processMassEditProgressSave(
                'Opportunity',
                $pageSize,
                OpportunitiesModule::getModuleLabelByTypeAndLanguage('Plural'),
                $dataProvider
            );
        }

        /**
         * Action for displaying a mass delete form and also action when that form is first submitted.
         * When the form is submitted, in the event that the quantity of models to delete is greater
         * than the pageSize, then once the pageSize quantity has been reached, the user will be
         * redirected to the makeMassDeleteProgressView.
         * In the mass delete progress view, a javascript refresh will take place that will call a refresh
         * action, usually makeMassDeleteProgressView.
         * If there is no need for a progress view, then a flash message will be added and the user will
         * be redirected to the list view for the model.  A flash message will appear providing information
         * on the delete records.
         * @see Controler->makeMassDeleteProgressView
         * @see Controller->processMassDelete
         * @see
         */
        public function actionMassDelete()
        {
            $pageSize = Yii::app()->pagination->resolveActiveForCurrentUserByType(
                            'massDeleteProgressPageSize');
            $opportunity = new Opportunity(false);

            $activeAttributes = $this->resolveActiveAttributesFromMassDeletePost();
            $dataProvider = $this->getDataProviderByResolvingSelectAllFromGet(
                new OpportunitiesSearchForm($opportunity),
                $pageSize,
                Yii::app()->user->userModel->id,
                null,
                'OpportunitiesSearchView'
            );
            $selectedRecordCount = static::getSelectedRecordCountByResolvingSelectAllFromGet($dataProvider);
            $opportunity = $this->processMassDelete(
                $pageSize,
                $activeAttributes,
                $selectedRecordCount,
                'OpportunitiesPageView',
                $opportunity,
                OpportunitiesModule::getModuleLabelByTypeAndLanguage('Plural'),
                $dataProvider
            );
            $massDeleteView = $this->makeMassDeleteView(
                $opportunity,
                $activeAttributes,
                $selectedRecordCount,
                OpportunitiesModule::getModuleLabelByTypeAndLanguage('Plural')
            );
            $view = new OpportunitiesPageView(ZurmoDefaultViewUtil::
                                         makeStandardViewForCurrentUser($this, $massDeleteView));
            echo $view->render();
        }

        /**
         * Action called in the event that the mass delete quantity is larger than the pageSize.
         * This action is called after the pageSize quantity has been delted and continues to be
         * called until the mass delete action is complete.  For example, if there are 20 records to delete
         * and the pageSize is 5, then this action will be called 3 times.  The first 5 are updated when
         * the actionMassDelete is called upon the initial form submission.
         */
        public function actionMassDeleteProgress()
        {
            $pageSize = Yii::app()->pagination->resolveActiveForCurrentUserByType(
                            'massDeleteProgressPageSize');
            $opportunity = new Opportunity(false);
            $dataProvider = $this->getDataProviderByResolvingSelectAllFromGet(
                new OpportunitiesSearchForm($opportunity),
                $pageSize,
                Yii::app()->user->userModel->id,
                null,
                'OpportunitiesSearchView'
            );
            $this->processMassDeleteProgress(
                'Opportunity',
                $pageSize,
                OpportunitiesModule::getModuleLabelByTypeAndLanguage('Plural'),
                $dataProvider
            );
        }

        public function actionModalList()
        {
            $modalListLinkProvider = new SelectFromRelatedEditModalListLinkProvider(
                                            $_GET['modalTransferInformation']['sourceIdFieldId'],
                                            $_GET['modalTransferInformation']['sourceNameFieldId'],
                                            $_GET['modalTransferInformation']['modalId']
            );
            echo ModalSearchListControllerUtil::setAjaxModeAndRenderModalSearchList($this, $modalListLinkProvider);
        }

        public function actionDelete($id)
        {
            $opportunity = Opportunity::GetById(intval($id));
            $opportunity->delete();
            $this->redirect(array($this->getId() . '/index'));
        }

        /**
         * Override to provide an opportunity specific label for the modal page title.
         * @see ZurmoModuleController::actionSelectFromRelatedList()
         */
        public function actionSelectFromRelatedList($portletId,
                                                    $uniqueLayoutId,
                                                    $relationAttributeName,
                                                    $relationModelId,
                                                    $relationModuleId,
                                                    $stateMetadataAdapterClassName = null)
        {
            parent::actionSelectFromRelatedList($portletId,
                                                    $uniqueLayoutId,
                                                    $relationAttributeName,
                                                    $relationModelId,
                                                    $relationModuleId);
        }

        protected static function getSearchFormClassName()
        {
            return 'OpportunitiesSearchForm';
        }

        public function actionExport()
        {
            $this->export('OpportunitiesSearchView');
        }
    }
?>
