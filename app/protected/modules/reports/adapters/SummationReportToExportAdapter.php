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
     * Helper class used to convert summationReport models into arrays
     */
    class SummationReportToExportAdapter extends ReportToExportAdapter
    {
        protected function makeData()
        {
            if ($this->rowsAreExpandable())
            {
                $this->makeDataWithExpandableRows();
            }
            else
            {
                parent::makeData();
            }
        }

        protected function rowsAreExpandable()
        {
            if (count($this->dataProvider->getReport()->getDrillDownDisplayAttributes()) > 0)
            {
                return true;
            }
            return false;
        }

        protected function makeDataWithExpandableRows()
        {
            $isFirstRow     = true;
            foreach ($this->dataForExport as $reportResultsRowData)
            {
                $data             = array();
                $this->headerData = array();
                $grandTotalsData  = array();
                $isFirstColumn         = true;
                foreach ($reportResultsRowData->getDisplayAttributes() as $key => $displayAttribute)
                {
                    $resolvedAttributeName = $displayAttribute->resolveAttributeNameForGridViewColumn($key);
                    $className             = $this->resolveExportClassNameForReportToExportValueAdapter($displayAttribute);
                    $params                = array();
                    $this->resolveParamsForCurrencyTypes($displayAttribute, $params);
                    $this->resolveParamsForGrandTotals($displayAttribute, $params, $isFirstRow, $isFirstColumn);
                    $adapter = new $className($reportResultsRowData, $resolvedAttributeName, $params);
                    $adapter->resolveData($data);
                    $adapter->resolveHeaderData($this->headerData);
                    $adapter->resolveGrandTotalsData($grandTotalsData);
                    $isFirstColumn = false;
                }
                $this->data[] = $data;
                $report = clone($this->report);
                $report->resolveGroupBysAsFilters($reportResultsRowData->getDataParamsForDrillDownAjaxCall());
                $this->resolveDrillDownDetailsData($report);
                $isFirstRow = false;
            }
            if (!empty($grandTotalsData))
            {
                $this->data[] = $grandTotalsData;
            }
        }

        protected function resolveDrillDownDetailsData($report)
        {
            $pageSize               = Yii::app()->pagination->resolveActiveForCurrentUserByType(
                                            'reportResultsSubListPageSize', $report->getModuleClassName());
            $dataProvider           = ReportDataProviderFactory::makeForSummationDrillDown($report, $pageSize);
            $totalItems = intval($dataProvider->calculateTotalItemCount());
            $dataProvider->getPagination()->setPageSize($totalItems);
            $reportToExportAdapter  = ReportToExportAdapterFactory::createReportToExportAdapter($report, $dataProvider);
            $drillDownHeaderData    = $reportToExportAdapter->getHeaderData();
            $drillDownData          = $reportToExportAdapter->getData();
            $this->data[]           = array_merge(array(null), $drillDownHeaderData);
            if (!empty($drillDownData))
            {
                foreach ($drillDownData as $row)
                {
                    $this->data[] = array_merge(array(null), $row);
                }
            }
        }
    }
?>