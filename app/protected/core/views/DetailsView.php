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
     * The base View for a module's details view.
     */
    abstract class DetailsView extends ModelView
    {
        protected $controllerId;

        protected $moduleId;

        protected $model;

        protected $title;

        /**
         * Constructs a detail view specifying the controller as
         * well as the model that will have its details displayed.
         */
        public function __construct($controllerId, $moduleId, $model, $title = null)
        {
            assert('$title == null || is_string($title)');
            $this->assertModelIsValid($model);
            $this->controllerId   = $controllerId;
            $this->moduleId       = $moduleId;
            $this->model          = $model;
            $this->modelClassName = get_class($model);
            $this->modelId        = $model->id;
            $this->title          = $title;
        }

        public static function assertModelIsValid($model)
        {
            assert('$model instanceof RedBeanModel || $model instanceof CFormModel || $model instanceof ModelForm');
        }

        /**
         * Renders content for a view including a layout title, form toolbar,
         * and form layout.
         * @return A string containing the element's content.
         */
        protected function renderContent()
        {
            $content  = '<div class="details-table">';
            $content .= $this->renderTitleContent();
            $content .= $this->resolveAndRenderActionElementMenu();
            $leftContent  = $this->renderBeforeFormLayoutForDetailsContent();
            $leftContent .= $this->renderFormLayout();
            $leftContent .= $this->renderAfterFormLayoutForDetailsContent();
            $leftContent  = $this->resolveLeftContentForSlidingPanels($leftContent);
            $content .= ZurmoHtml::tag('div', array('class' => 'left-column full-width'), $leftContent);
            $content .= $this->renderRightSideContent();
            $content .= $this->renderAfterRightSideContent();
            $content .= '</div>';
            $content .= $this->renderAfterDetailsTable();
            return $content;
        }

        /**
         * Override to adding sliding panel support. @see ContactDetailsPortletView for an example.
         * @param $content
         * @return mixed
         */
        protected function resolveLeftContentForSlidingPanels($content)
        {
            return $content;
        }

        protected function resolveAndRenderActionElementMenu()
        {
            return $this->renderWrapperAndActionElementMenu();
        }

        protected function renderRightSideContent($form = null)
        {
        }

        protected function renderBeforeFormLayoutForDetailsContent()
        {
        }

        protected function renderAfterFormLayoutForDetailsContent()
        {
        }

        protected function renderAfterRightSideContent()
        {
        }

        protected function renderAfterDetailsTable()
        {
        }

        /**
         * Render a form layout.
         * @param $form If the layout is editable, then pass a $form otherwise it can
         * be null.
         * @return A string containing the element's content.
          */
        protected function renderFormLayout($form = null)
        {
            assert('$form == null || $form instanceof ZurmoActiveForm');
            $maxCellsPerRow               = $this->getMaxCellsPerRow();
            $metadata                     = $this->getFormLayoutMetadata();
            $metadataWithRenderedElements = $this->resolveMetadataWithRenderedElements($metadata, $maxCellsPerRow, $form);
            $this->afterResolveMetadataWithRenderedElements($metadataWithRenderedElements, $form);
            if ($form != null)
            {
                $errorSummaryContent      = $form->errorSummary($this->getModel());
            }
            else
            {
                $errorSummaryContent = null;
            }
            $formLayout = new DetailsViewFormLayout($metadataWithRenderedElements, $maxCellsPerRow,
                                                    $errorSummaryContent, $this->getFormLayoutUniqueId());
            $formLayout->alwaysShowErrorSummary = $this->alwaysShowErrorSummary();
            $formLayout->labelsHaveOwnCells($this->doesLabelHaveOwnCell());
            $formLayout->setMorePanelsLinkLabel($this->getMorePanelsLinkLabel());
            $formLayout->setLessPanelsLinkLabel($this->getLessPanelsLinkLabel());
            return $formLayout->render();
        }

        /**
         * Given metadata, resolve the element information into the rendered element content and return the metadata
         * with rendered element content instead of element information.
         * @param array $metadata
         * @param integer $maxCellsPerRow
         * @param object $form ZurmoActiveForm or null
         */
        protected function resolveMetadataWithRenderedElements($metadata, $maxCellsPerRow, $form)
        {
            assert('is_array($metadata)');
            assert('is_int($maxCellsPerRow)');
            assert('$form == null || $form instanceof ZurmoActiveForm');
            $maximumColumnCount = DetailsViewFormLayout::getMaximumColumnCountForAllPanels($metadata);
            foreach ($metadata['global']['panels'] as $panelNumber => $panel)
            {
                if ($this->shouldDisplayPanel(ArrayUtil::getArrayValue($panel, 'detailViewOnly')))
                {
                    foreach ($panel['rows'] as $rowIndex => $row)
                    {
                        foreach ($row['cells'] as $cellIndex => $cell)
                        {
                            if (is_array($cell['elements']) && $this->shouldDisplayCell(ArrayUtil::getArrayValue($cell, 'detailViewOnly')))
                            {
                                foreach ($cell['elements'] as $elementIndex => $elementInformation)
                                {
                                    if (count($row['cells']) == 1 && count($row['cells']) < $maxCellsPerRow &&
                                        count($row['cells']) < $maximumColumnCount)
                                    {
                                        $elementInformation['wide'] = true;
                                    }
                                    $this->resolveElementInformationDuringFormLayoutRender($elementInformation);
                                    Yii::app()->custom->resolveElementInformationDuringFormLayoutRender($this, $elementInformation);
                                    $elementclassname = $elementInformation['type'] . 'Element';
                                    $element  = new $elementclassname($this->getModel(), $elementInformation['attributeName'],
                                                                      $form, array_slice($elementInformation, 2));
                                    $this->resolveElementDuringFormLayoutRender($element);
                                    $metadata['global']['panels'][$panelNumber]['rows']
                                    [$rowIndex]['cells'][$cellIndex]['elements'][$elementIndex] = $element->render();
                                }
                            }
                            else
                            {
                                foreach ($cell['elements'] as $elementIndex => $elementInformation)
                                {
                                    $metadata['global']['panels'][$panelNumber]['rows']
                                    [$rowIndex]['cells'][$cellIndex]['elements'][$elementIndex] = null;
                                }
                            }
                        }
                    }
                }
                else
                {
                    unset($metadata['global']['panels'][$panelNumber]);
                }
            }
            return $metadata;
        }

        /**
         * Given an array of metadata, resolve what the maximum amount of cells present are in any given row.
         * @param array $metadata
         */
        protected function resolveMaxCellsPresentInAnyRow($metadata)
        {
            assert('is_array($metadata)');
            $maxCellsPresent = 1;
            if (!isset($metadata['global']['panels']))
            {
                return $maxCellsPresent;
            }
            foreach ($metadata['global']['panels'] as $panelNumber => $panel)
            {
                foreach ($panel['rows'] as $rowIndex => $row)
                {
                    $maxCellsPresentInThisRow = 0;
                    foreach ($row['cells'] as $cellIndex => $cell)
                    {
                        $maxCellsPresentInThisRow++;
                    }
                    if ($maxCellsPresentInThisRow > $maxCellsPresent)
                    {
                        $maxCellsPresent = $maxCellsPresentInThisRow;
                    }
                }
            }
            return $maxCellsPresent;
        }

        /**
         * @return true if the label has its own TD next to the TD of the input. Override if the label is on
         * top of the input, in which case it does not need its own cell.
         */
        protected function doesLabelHaveOwnCell()
        {
            return true;
        }

        /**
         * Override if you need to do any special processing of the metadata array prior to it being rendered.
         * @param array $metadataWithRenderedElements
         */
        protected function afterResolveMetadataWithRenderedElements(& $metadataWithRenderedElements, $form)
        {
        }

        protected function getMaxCellsPerRow()
        {
            $designerRulesType          = static::getDesignerRulesType();
            if ($designerRulesType == null)
            {
                $designerRulesType      = self::getDesignerRulesType();
            }
            $designerRulesClassName = $designerRulesType . 'DesignerRules';
            $designerRules          = new $designerRulesClassName();
            return $designerRules->maxCellsPerRow();
        }

        /**
         * Returns meta data for use in automatically generating the view.
         * The meta data is comprised of panels, rows, and then cells. Each
         * cell can have 1 or more elements.
         *
         * The element takes 3 parameters.
         * The first parameter is 'attributeName'. The
         * second parameter is 'type' and refers to the element type. Using a
         * type of 'Text' would utilize the TextElement class. The third parameter
         * is 'wide' and refers to how many cells the field should span. An example
         * of the 'wide' => true usage would be for a text description field.
         * Here is an example meta data that
         * defines a 2 row x 2 cell layout.
         *
         * @code
            <?php
                $metadata = array(
                    'panels' => array(
                        array(
                            'rows' => array(
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('field' => 'name', 'type' => 'Text'),
                                            ),
                                        ),
                                        array(
                                            'elements' => array(
                                                array('field' => 'officePhone', 'type' => 'Text'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('field' => 'industry', 'type' => 'DropDown'),
                                            ),
                                        ),
                                        array(
                                            'elements' => array(
                                                array('field' => 'officeFax', 'type' => 'Text'),
                                            ),
                                        ),
                                    )
                                ),
                            ),
                        ),
                    ),
                );
            ?>
         * @endcode
         *
         */
        public static function getDefaultMetadata()
        {
            return array();
        }

        /**
         * Return the model that will have its details displayed.
         */
        protected function getModel()
        {
            return $this->model;
        }

        protected function shouldDisplayCell($detailViewOnly)
        {
            return true;
        }

        protected function shouldDisplayPanel($detailViewOnly)
        {
            return true;
        }

        public static function getDesignerRulesType()
        {
            return 'DetailsView';
        }

        /**
         * Override sub-class if you need to add anything to the ElementInformation
         * as you are parsing the form layout
         */
        protected function resolveElementInformationDuringFormLayoutRender(& $elementInformation)
        {
        }

        /**
         * Override sub-class if you need to set anything into the element object.
         */
        protected function resolveElementDuringFormLayoutRender(& $element)
        {
        }

        /**
         * Gets the metadata for this view.
         * Override if you need to make the metadata
         * dynamically or change the way the metadata
         * is retreived
         * @return array view metadata
         */
        protected function getFormLayoutMetadata()
        {
            return self::getMetadata();
        }

        protected static function assertMetadataIsValid(array $metadata)
        {
            parent::assertMetadataIsValid($metadata);
            $attributeNames = array();
            $derivedTypes   = array();
            assert('!isset($metadata["global"]["panelsDisplayType"]) || is_int($metadata["global"]["panelsDisplayType"])');
        }

        /**
         * For the given view, return the label used when a link is displayed to show additional panels in the view.
         * @return string label.
         */
        protected function getMorePanelsLinkLabel()
        {
            return Zurmo::t('Core', 'More Details');
        }

        /**
         * For the given view, return the label used when a link is displayed to show less panels in the view.
         * @return string label.
         */
        protected function getLessPanelsLinkLabel()
        {
            return Zurmo::t('Core', 'Fewer Details');
        }

        public function getTitle()
        {
            return $this->title;
        }

        protected function alwaysShowErrorSummary()
        {
            return false;
        }

        /**
         * Gets form layout unique id
         * @return null
         */
        protected function getFormLayoutUniqueId()
        {
            return null;
        }
    }
?>
