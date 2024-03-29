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

    class Redactor extends ZurmoWidget
    {
        public $scriptFile      = 'redactor.min.js';

        public $cssFile         = 'redactor.css';

        public $htmlOptions;

        public $content;

        public $buttons         = "['html', '|', 'formatting', 'bold', 'italic', 'deleted', '|',
                                   'unorderedlist', 'orderedlist', 'outdent', 'indent', '|', 'table', 'link', '|',
                                   'fontcolor', 'backcolor', '|', 'alignleft', 'aligncenter', 'alignright', 'justify', '|',
                                   'horizontalrule']";

        public $source          = "false";

        public $paragraphy      = "true";

        public $cleanup         = "true";

        public $fullpage        = "true";

        public $iframe          = "false";

        public $minHeight       = 100;

        public $convertDivs     = "false";

        public $observeImages   = "false";

        public $wym             = "false";

        public $deniedTags      = "['html', 'head', 'link', 'body', 'meta', 'script', 'style', 'applet']";

        public function run()
        {
            $id         = $this->htmlOptions['id'];
            $name       = $this->htmlOptions['name'];
            $javaScript = "
                    $(document).ready(
                        function()
                        {
                            $('#{$id}').redactor(
                            {
                                buttons:        {$this->buttons},
                                cleanup:        {$this->cleanup},
                                convertDivs:    {$this->convertDivs},
                                deniedTags:     {$this->deniedTags},
                                fullpage:       {$this->fullpage},
                                iframe:         {$this->iframe},
                                minHeight:      {$this->minHeight},
                                observeImages:  {$this->observeImages},
                                source:         {$this->source},
                                paragraphy:     {$this->paragraphy},
                                wym:            {$this->wym},
                            });
                        }
                    );";
            Yii::app()->getClientScript()->registerScript(__CLASS__ . '#' . $this->getId(), $javaScript);
            echo "<textarea id='{$id}' name='{$name}'>" . CHtml::encode($this->content) . "</textarea>";
        }

        protected function resolvePackagePath()
        {
            if ($this->scriptUrl === null || $this->themeUrl === null)
            {
                $cs = Yii::app()->getClientScript();
                if ($this->scriptUrl === null)
                {
                    $this->scriptUrl = Yii::app()->getAssetManager()->publish(
                                        Yii::getPathOfAlias('application.core.widgets.assets.redactor'));
                }
            }
        }
    }
?>