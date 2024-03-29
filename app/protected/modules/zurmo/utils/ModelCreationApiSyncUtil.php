<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2013 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
     * details.
     *
     * You should have received a copy of the GNU General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 27 North Wacker Drive
     * Suite 370 Chicago, IL 60606. or at email address contact@zurmo.com.
     *
     * The interactive user interfaces in original and modified versions
     * of this program must display Appropriate Legal Notices, as required under
     * Section 5 of the GNU General Public License version 3.
     *
     * In accordance with Section 7(b) of the GNU General Public License version 3,
     * these Appropriate Legal Notices must retain the display of the Zurmo
     * logo and Zurmo copyright notice. If the display of the logo is not reasonably
     * feasible for technical reasons, the Appropriate Legal Notices must display the words
     * "Copyright Zurmo Inc. 2013. All rights reserved".
     ********************************************************************************/
    abstract class ModelCreationApiSyncUtil
    {
        const TABLE_NAME = 'modelcreationapisync';

        /**
         * Build modelcreationapisync table
         */
        public static function buildTable()
        {
            $schema = static::getTableSchema();
            CreateOrUpdateExistingTableFromSchemaDefinitionArrayUtil::generateOrUpdateTableBySchemaDefinition(
                                                                                        $schema, new MessageLogger());
        }

        /**
         * Insert item into modelcreationapisync table
         * @param $serviceName
         * @param $modelId
         * @param $modelClassName
         * @param $dateTime
         */
        public static function insertItem($serviceName, $modelId, $modelClassName, $dateTime)
        {
            assert('is_string($serviceName)');
            assert('is_int($modelId)');
            assert('is_string($dateTime)');
            $sql = "INSERT INTO " . static::TABLE_NAME .
                " VALUES (null, '{$serviceName}', '{$modelId}', '{$modelClassName}', '{$dateTime}')";
            ZurmoRedBean::exec($sql);
        }

        protected static function getTableSchema()
        {
            return array(static::TABLE_NAME =>  array('columns' => array(
                                                                    array(
                                                                        'name' => 'servicename',
                                                                        'type' => 'VARCHAR(50)',
                                                                        'unsigned' => null,
                                                                        'notNull' => 'NOT NULL', // Not Coding Standard
                                                                        'collation' => 'COLLATE utf8_unicode_ci',
                                                                        'default' => null,
                                                                    ),
                                                                    array(
                                                                        'name' => 'modelid',
                                                                        'type' => 'INT(11)',
                                                                        'unsigned' => 'UNSIGNED',
                                                                        'notNull' => 'NOT NULL', // Not Coding Standard
                                                                        'collation' => null,
                                                                        'default' => null,
                                                                    ),
                                                                    array(
                                                                        'name' => 'modelclassname',
                                                                        'type' => 'VARCHAR(50)',
                                                                        'unsigned' => null,
                                                                        'notNull' => 'NOT NULL', // Not Coding Standard
                                                                        'collation' => 'COLLATE utf8_unicode_ci',
                                                                        'default' => null,
                                                                    ),
                                                                    array(
                                                                        'name' => 'createddatetime',
                                                                        'type' => 'DATETIME',
                                                                        'unsigned' => null,
                                                                        'notNull' => 'NULL', // Not Coding Standard
                                                                        'collation' => null,
                                                                        'default' => 'NULL', // Not Coding Standard
                                                                    ),
                                                                ),
                                                        'indexes' => array(),
                                                            )
                                                        );
        }

        /**
         * Delete item from modelcreationapisync table
         * @param $serviceName
         * @param $modelId
         * @param $modelClassName
         */
        public static function deleteItem($serviceName, $modelId, $modelClassName)
        {
            $sql = "DELETE FROM " . self::TABLE_NAME .
                " where servicename = '{$serviceName}'" .
                " AND modelid = '{$modelId}'" .
                " AND modelclassname = '{$modelClassName}'";
            ZurmoRedBean::exec($sql);
        }
    }
?>