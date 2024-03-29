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

    class MessageTranslation extends RedBeanModel
    {
        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'translation',
                    'language',
                ),
                'relations' => array(
                    'messagesource'   => array(static::HAS_ONE, 'MessageSource', static::OWNED),
                ),
                'rules' => array(
                    array('translation',        'required'),
                    array('translation',        'type', 'type' => 'blob'),
                    array('language',           'required'),
                    array('language',           'type', 'type' => 'string'),
                    array('language',           'length',  'min'  => 1, 'max' => 16),
                ),
                'elements' => array(
                    'messagesource' => 'MessageSource',
                ),
                'indexes' => array(
                    'sourceLanguageTranslation' => array(
                        'members'   => array('messagesource_id', 'language', 'translation(767)'),
                        'unique'    => true,
                    )
                ),
            );
            return $metadata;
        }

        /**
         * Gets a model from the database by source message id and langcode
         * @param $sourceId Integer Id of the source message
         * @param $languageCode String Language code of the translation
         * @param $modelClassName Pass only when getting it at runtime
         *                        gets the wrong name.
         * @return A model of the type of the extending model.
         */
        public static function getBySourceIdAndLangCode($sourceId, $languageCode, $modelClassName = null)
        {
            assert('intval($sourceId) && $sourceId > 0');
            assert('!empty($languageCode)');
            assert('$modelClassName === null || is_string($modelClassName) && $modelClassName != ""');
            if ($modelClassName === null)
            {
                $modelClassName = get_called_class();
            }
            $tableName = self::getTableName($modelClassName);
            $bean = ZurmoRedBean::findOne(
                               $tableName,
                               ' messagesource_id = :sourceId AND language = :languageCode',
                               array(
                                     ':sourceId'     => $sourceId,
                                     ':languageCode' => $languageCode
                                     )
                               );
            assert('$bean === false || $bean instanceof RedBean_OODBBean');
            if (!is_object($bean))
            {
                throw new NotFoundException();
            }
            return self::makeModel($bean, $modelClassName);
        }

        /**
         * Adds new message translation to the database
         *
         * @param String $languageCode Languagecode of the translation
         * @param MessageSource $sourceModel MessageSource model for the relation
         * @param String $translation The translation
         *
         * @return Instance of the MessageTranslation model for created translation
         */
        public static function addNewTranslation($languageCode, $sourceModel, $translation)
        {
            assert('is_string($languageCode) && !empty($languageCode)');
            assert('$sourceModel instanceof MessageSource');
            assert('is_string($translation) && !empty($translation)');
            $model = new MessageTranslation();
            $model->language      = $languageCode;
            $model->messagesource = $sourceModel;
            $model->translation   = $translation;
            if (!$model->save())
            {
                throw new FailedToSaveModelException();
            }

            return $model;
        }

        /**
         * Updates the translation of the current model
         *
         * @param String $translation The translation
         *
         * @return The updated model
         */
        public function updateTranslation($translation)
        {
            assert('!empty($translation)');
            $this->translation = $translation;
            if (!$this->save())
            {
                throw new FailedToSaveModelException();
            }

            return $this;
        }
    }
?>
