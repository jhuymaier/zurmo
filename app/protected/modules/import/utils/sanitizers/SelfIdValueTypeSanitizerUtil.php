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
     * Sanitizer for attributes that are ids. This would be used if mapping an id for the model that is being imported.
     *
     * Override to handle when the value is an id that represents the model itself.
     */
    class SelfIdValueTypeSanitizerUtil extends IdValueTypeSanitizerUtil
    {
        public static function getLinkedMappingRuleType()
        {
            return 'IdValueType';
        }

        protected function resolveForUnfoundModel(RedBean_OODBBean $rowBean)
        {
            if ($this->mappingRuleData["type"] == IdValueTypeMappingRuleForm::ZURMO_MODEL_ID)
            {
                $label = Zurmo::t('ImportModule', 'Was not found and this row will be skipped during import.');
                $this->shouldSkipRow = true;
            }
            else
            {
                $label = Zurmo::t('ImportModule', 'Was not found and will create a new record during import.');
            }
            $this->analysisMessages[] = $label;
        }

        /**
         * Given a value that is either a zurmo id or an external system id, resolve that the
         * value is valid.  If the value is not valid then an InvalidValueToSanitizeException is thrown.
         * @param mixed $value
         * @return sanitized value
         * @throws InvalidValueToSanitizeException
         * @throws ExternalSystemIdNotFoundException
         * @throws NotFoundException
         */
        public function sanitizeValue($value)
        {
            assert('is_string($this->attributeName) && $this->attributeName == "id"');
            if ($value == null)
            {
                return $value;
            }
            if ($this->mappingRuleData["type"] == IdValueTypeMappingRuleForm::ZURMO_MODEL_ID)
            {
                try
                {
                    if ((int)$value <= 0)
                    {
                        throw new NotFoundException();
                    }
                    $modelClassName = $this->modelClassName;
                    $modelClassName::getById((int)$value);
                    return (int)$value;
                }
                catch (NotFoundException $e)
                {
                    throw new InvalidValueToSanitizeException(Zurmo::t('ImportModule', 'ID specified did not match any existing records.'));
                }
            }
            elseif ($this->mappingRuleData["type"] == IdValueTypeMappingRuleForm::EXTERNAL_SYSTEM_ID)
            {
                try
                {
                    $model = static::getModelByExternalSystemIdAndModelClassName($value, $this->modelClassName);
                    return $model->id;
                }
                catch (NotFoundException $e)
                {
                    throw new ExternalSystemIdNotFoundException();
                }
            }
        }
    }
?>