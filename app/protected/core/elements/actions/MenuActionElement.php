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

    abstract class MenuActionElement extends ActionElement
    {
        public function render()
        {
            $menuItems   = $this->renderMenuItem();
            $cClipWidget = new CClipWidget();
            $cClipWidget->beginClip("ActionMenu");
            $cClipWidget->widget('application.core.widgets.DividedMenu', array(
                'items'       => array($menuItems),
                'htmlOptions' => $this->resolveHtmlOptionsForRendering(),
            ));
            $cClipWidget->endClip();
            return $cClipWidget->getController()->clips['ActionMenu'];
        }

        public function renderMenuItem()
        {
            return array('label'               => $this->getDefaultLabel(),
                         'url'                 => $this->getDefaultRoute(),
                         'itemOptions'         => array('iconClass' => $this->getIconClass()),
                         'dynamicLabel'        => $this->getDynamicLabel(),
                         'dynamicContent'      => $this->getDynamicContent(),
                         'items'               => $this->getMenuItems());
        }

        protected function resolveHtmlOptionsForRendering()
        {
            return $this->getHtmlOptions();
        }

        protected function resolveHtmlOptionsForRenderingMenuItem()
        {
            return null;
        }

        protected function getMenuItems()
        {
            return null;
        }

        protected function getDynamicLabel()
        {
            if (!isset($this->params['dynamicLabel']))
            {
                return null;
            }
            return $this->params['dynamicLabel'];
        }

        protected function getDynamicContent()
        {
            if (!isset($this->params['dynamicContent']))
            {
                return null;
            }
            return $this->params['dynamicContent'];
        }

        protected function getIconClass()
        {
            if (!isset($this->params['iconClass']))
            {
                return null;
            }
            return $this->params['iconClass'];
        }
    }
?>