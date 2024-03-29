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

    abstract class ReadPermissionsOptimizationUtil
    {
        /**
         * At some point if performance is a problem with rebuilding activity models, then the stored procedure
         * needs to be refactored to somehow support more joins dynamically.
         * @see https://www.pivotaltracker.com/story/show/38804909
         * @param boolean $overwriteExistingTables
         * @param boolean $forcePhp
         */
        public static function rebuild($overwriteExistingTables = true, $forcePhp = false, $messageStreamer = null)
        {
            //Forcing php way until we can fix failing tests here: AccountReadPermissionsOptimizationScenariosTest
            $forcePhp = true;
            assert('is_bool($overwriteExistingTables)');
            assert('is_bool($forcePhp)');
            foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
            {
                $mungeTableName     = self::getMungeTableName($modelClassName);
                $readTableExists    = ZurmoRedBean::$writer->doesTableExist($mungeTableName);
                if (!$overwriteExistingTables && $readTableExists)
                {
                    if (isset($messageStreamer))
                    {
                        $messageStreamer->add(Zurmo::t('ZurmoModule', "Skipping {{tableName}}",
                                                array('{{tableName}}' => $mungeTableName)));
                    }
                    // skip if we don't want to overwrite existing tables and table already exists
                    continue;
                }
                if (isset($messageStreamer))
                {
                    $messageStreamer->add(Zurmo::t('ZurmoModule', "Building {{tableName}}",
                                                    array('{{tableName}}' => $mungeTableName)));
                }

                if (!SECURITY_OPTIMIZED || $forcePhp)
                {
                    self::rebuildViaSlowWay($modelClassName);
                }
                else
                {
                    //models that extend activity are special and can only be done with the PHP process.  They cannot
                    //be done using the stored procedure because it does not support the extra joins needed to determine
                    //which securable items to look at.
                    if (is_subclass_of($modelClassName, 'Activity'))
                    {
                        self::rebuildViaSlowWay($modelClassName);
                    }
                    else
                    {
                        $modelTableName     = RedBeanModel::getTableName($modelClassName);
                        if (!is_subclass_of($modelClassName, 'OwnedSecurableItem'))
                        {
                            throw new NotImplementedException();
                        }
                        if (is_subclass_of($modelClassName, 'Person'))
                        {
                            if ($modelClassName != 'Contact')
                            {
                                throw new NotSupportedException();
                            }
                            else
                            {
                                $modelTableName = Person::getTableName('Person');
                            }
                        }
                        ZurmoDatabaseCompatibilityUtil::
                            callProcedureWithoutOuts("rebuild('$modelTableName', '$mungeTableName')");
                    }
                }
            }
        }

        protected static function rebuildViaSlowWay($modelClassName)
        {
            // The slow way will remain here as documentation
            // for what the optimized way is doing.
            $mungeTableName  = self::getMungeTableName($modelClassName);
            self::recreateTable($mungeTableName);
            //Specifically call RedBeanModel to avoid the use of the security in OwnedSecurableItem since for
            //rebuild it needs to look at all models regardless of permissions of the current user.
            $modelCount = RedBeanModel::getCount(null, null, $modelClassName);
            $subset = intval($modelCount / 20);
            if ($subset < 100)
            {
                $subset = 100;
            }
            elseif ($subset > 1000)
            {
                $subset = 1000;
            }
            $users  = User::getAll();
            $groups = Group::getAll();
            $roles  = Role::getAll();
            for ($i = 0; $i < $modelCount; $i += $subset)
            {
                //Specifically call RedBeanModel to avoid the use of the security in OwnedSecurableItem since for
                //rebuild it needs to look at all models regardless of permissions of the current user.
                $models = RedBeanModel::getSubset(null, $i, $subset, null, null, $modelClassName);
                foreach ($models as $model)
                {
                    assert('$model instanceof SecurableItem');
                    $securableItemId = $model->getClassId('SecurableItem');
                    foreach ($users as $user)
                    {
                        list($allowPermissions, $denyPermissions) = $model->getExplicitActualPermissions($user);
                        $effectiveExplicitPermissions = $allowPermissions & ~$denyPermissions;
                        if (($effectiveExplicitPermissions & Permission::READ) == Permission::READ)
                        {
                            self::incrementCount($mungeTableName, $securableItemId, $user);
                        }
                    }

                    foreach ($groups as $group)
                    {
                        list($allowPermissions, $denyPermissions) = $model->getExplicitActualPermissions($group);
                        $effectiveExplicitPermissions = $allowPermissions & ~$denyPermissions;
                        if (($effectiveExplicitPermissions & Permission::READ) == Permission::READ)
                        {
                            self::incrementCount($mungeTableName, $securableItemId, $group);
                            foreach ($group->users as $user)
                            {
                                if ($user->role->id > 0)
                                {
                                    self::incrementParentRolesCounts($mungeTableName, $securableItemId, $user->role);
                                }
                            }
                            foreach ($group->groups as $subGroup)
                            {
                                self::processNestedGroupWhereParentHasReadPermissionOnSecurableItem(
                                      $mungeTableName, $securableItemId, $subGroup);
                            }
                        }
                    }
                    foreach ($roles as $role)
                    {
                        $count = self::getRoleMungeCount($model, $role);
                        assert('$count >= 0');
                        if ($count > 0)
                        {
                            self::setCount($mungeTableName, $securableItemId, $role, $count);
                        }
                    }
                }
            }
        }

        protected static function processNestedGroupWhereParentHasReadPermissionOnSecurableItem(
                                  $mungeTableName, $securableItemId, Group $group)
        {
            assert('is_string($mungeTableName) && $mungeTableName != ""');
            assert('is_int($securableItemId) && $securableItemId > 0');
            self::incrementCount($mungeTableName, $securableItemId, $group);
            foreach ($group->users as $user)
            {
                if ($user->role->id > 0)
                {
                    self::incrementParentRolesCounts($mungeTableName, $securableItemId, $user->role);
                }
            }
            foreach ($group->groups as $subGroup)
            {
                self::processNestedGroupWhereParentHasReadPermissionOnSecurableItem(
                      $mungeTableName, $securableItemId, $subGroup);
            }
        }

        protected static function getRoleMungeCount(SecurableItem $securableItem, Role $role)
        {
            $count = 0;
            foreach ($role->roles as $subRole)
            {
                $count += self::getSubRoleMungeCount($securableItem, $subRole);
            }
            return $count;
        }

        protected static function getSubRoleMungeCount(SecurableItem $securableItem, Role $role)
        {
            $count = self::getImmediateRoleMungeCount($securableItem, $role);
            foreach ($role->roles as $subRole)
            {
                $count += self::getSubRoleMungeCount($securableItem, $subRole);
            }
            return $count;
        }

        protected static function getImmediateRoleMungeCount(SecurableItem $securableItem, Role $role)
        {
            $count = 0;
            foreach ($role->users as $user)
            {
                if ($securableItem->owner->isSame($user))
                {
                    $count++;
                }
                list($allowPermissions, $denyPermissions) = $securableItem->getExplicitActualPermissions($user);
                $effectiveExplicitPermissions = $allowPermissions & ~$denyPermissions;
                if (($effectiveExplicitPermissions & Permission::READ) == Permission::READ)
                {
                    $count++;
                }
                foreach ($user->groups as $group)
                {
                    $count += self::getGroupMungeCount($securableItem, $group);
                }
            }
            return $count;
        }

        protected static function getGroupMungeCount(SecurableItem $securableItem, Group $group)
        {
            $count = 0;
            list($allowPermissions, $denyPermissions) = $securableItem->getExplicitActualPermissions($group);
            $effectiveExplicitPermissions = $allowPermissions & ~$denyPermissions;
            if (($effectiveExplicitPermissions & Permission::READ) == Permission::READ)
            {
                $count++;
            }
            if ($group->group->id > 0 && !$group->group->isSame($group)) // Prevent cycles in database auto build.
            {
                $count += self::getGroupMungeCount($securableItem, $group->group);
            }
            return $count;
        }

        // SecurableItem create, assigned, or deleted.

        // Past tense implies the method must be called immediately after the associated operation.
        public static function ownedSecurableItemCreated(OwnedSecurableItem $ownedSecurableItem)
        {
            self::ownedSecurableItemOwnerChanged($ownedSecurableItem);
        }

        /**
         * @param OwnedSecurableItem $ownedSecurableItem
         * @param User $oldUser
         */
        public static function ownedSecurableItemOwnerChanged(OwnedSecurableItem $ownedSecurableItem, User $oldUser = null)
        {
            $modelClassName = get_class($ownedSecurableItem);
            assert('$modelClassName != "OwnedSecurableItem"');
            $mungeTableName = self::getMungeTableName($modelClassName);
            if ($oldUser !== null && $oldUser->role->id > 0)
            {
                self::decrementParentRolesCounts($mungeTableName, $ownedSecurableItem->getClassId('SecurableItem'), $oldUser->role);
                self::garbageCollect($mungeTableName);
            }
            if ($ownedSecurableItem->owner->role->id > 0)
            {
                self::incrementParentRolesCounts($mungeTableName, $ownedSecurableItem->getClassId('SecurableItem'), $ownedSecurableItem->owner->role);
            }
        }

        // Being implies the the method must be called just before the associated operation.
        // The object is needed before the delete occurs and the delete cannot fail.
        public static function securableItemBeingDeleted(SecurableItem $securableItem) // Call being methods before the destructive operation.
        {
            $modelClassName = get_class($securableItem);
            assert('$modelClassName != "OwnedSecurableItem"');
            $mungeTableName = self::getMungeTableName($modelClassName);
            $securableItemId = $securableItem->getClassId('SecurableItem');
            ZurmoRedBean::exec("delete from $mungeTableName
                     where       securableitem_id = $securableItemId");
        }

        // Permissions added or removed.

        /**
         * @param SecurableItem $securableItem
         * @param User $user
         */
        public static function securableItemGivenPermissionsForUser(SecurableItem $securableItem, User $user)
        {
            $modelClassName = get_class($securableItem);
            assert('$modelClassName != "OwnedSecurableItem"');
            $mungeTableName = self::getMungeTableName($modelClassName);
            $securableItemId = $securableItem->getClassId('SecurableItem');
            self::incrementCount($mungeTableName, $securableItemId, $user);
            if ($user->role->id > 0)
            {
                self::incrementParentRolesCounts($mungeTableName, $securableItemId, $user->role);
            }
        }

        /**
         * @param SecurableItem $securableItem
         * @param Group $group
         */
        public static function securableItemGivenPermissionsForGroup(SecurableItem $securableItem, Group $group)
        {
            $modelClassName = get_class($securableItem);
            assert('$modelClassName != "OwnedSecurableItem"');
            $mungeTableName = self::getMungeTableName($modelClassName);
            $securableItemId = $securableItem->getClassId('SecurableItem');
            self::incrementCount($mungeTableName, $securableItemId, $group);
            foreach ($group->users as $user)
            {
                if ($user->role->id > 0)
                {
                    self::incrementParentRolesCounts($mungeTableName, $securableItemId, $user->role);
                }
            }
            foreach ($group->groups as $subGroup)
            {
                self::securableItemGivenPermissionsForGroup($securableItem, $subGroup);
            }
        }

        /**
         * @param SecurableItem $securableItem
         * @param User $user
         */
        public static function securableItemLostPermissionsForUser(SecurableItem $securableItem, User $user)
        {
            $modelClassName = get_class($securableItem);
            assert('$modelClassName != "OwnedSecurableItem"');
            $mungeTableName = self::getMungeTableName($modelClassName);
            $securableItemId = $securableItem->getClassId('SecurableItem');
            self::decrementCount($mungeTableName, $securableItemId, $user);
            if ($user->role->id > 0)
            {
                self::decrementParentRolesCounts($mungeTableName, $securableItemId, $user->role);
            }
            self::garbageCollect($mungeTableName);
        }

        /**
         * @param SecurableItem $securableItem
         * @param Group $group
         */
        public static function securableItemLostPermissionsForGroup(SecurableItem $securableItem, Group $group)
        {
            $modelClassName = get_class($securableItem);
            assert('$modelClassName != "OwnedSecurableItem"');
            $mungeTableName = self::getMungeTableName($modelClassName);
            $securableItemId = $securableItem->getClassId('SecurableItem');
            self::decrementCount($mungeTableName, $securableItemId, $group);
            foreach ($group->users as $user)
            {
                self::securableItemLostPermissionsForUser($securableItem, $user);
            }
            foreach ($group->groups as $subGroup)
            {
                self::securableItemLostPermissionsForGroup($securableItem, $subGroup);
            }
            self::garbageCollect($mungeTableName);
        }

        // User operations.

        /**
         * @param $user
         */
        public static function userBeingDeleted($user) // Call being methods before the destructive operation.
        {
            foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
            {
                $mungeTableName = self::getMungeTableName($modelClassName);
                if ($user->role->id > 0)
                {
                    self::decrementParentRolesCountsForAllSecurableItems($mungeTableName, $user->role);
                    self::garbageCollect($mungeTableName);
                }
                $userId = $user->id;
                ZurmoRedBean::exec("delete from $mungeTableName
                         where       munge_id = 'U$userId'");
            }
        }

        // Group operations.

        /**
         * @param Group $group
         * @param User $user
         */
        public static function userAddedToGroup(Group $group, User $user)
        {
            foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
            {
                $mungeTableName = self::getMungeTableName($modelClassName);
                $groupId = $group->id;
                $sql = "select securableitem_id
                        from   $mungeTableName
                        where  munge_id = concat('G', $groupId)";
                $securableItemIds = ZurmoRedBean::getCol($sql);
                self::bulkIncrementParentRolesCounts($mungeTableName, $securableItemIds, $user->role);
                /*
                 * This extra step is not needed. See slide 21.  This is similar to userBeingRemovedFromRole in that
                 * the above query already is trapping the information needed.
                    Follow the same process for any upstream groups that the group is a member of.
                */
            }
        }

        /**
         * @param Group $group
         * @param User $user
         */
        public static function userRemovedFromGroup(Group $group, User $user)
        {
            foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
            {
                $mungeTableName = self::getMungeTableName($modelClassName);
                $groupId = $group->id;
                $sql = "select securableitem_id
                        from   $mungeTableName
                        where  munge_id = concat('G', $groupId)";
                $securableItemIds = ZurmoRedBean::getCol($sql);
                self::bulkDecrementParentRolesCounts($mungeTableName, $securableItemIds, $user->role);
                /*
                 * This extra step is not needed. See slide 22. This is similar to userBeingRemovedFromRole or
                 * userAddedToGroup in that the above query is already trapping the information needed.
                    Follow the same process for any upstream groups that the group is a member of.
                */
                self::garbageCollect($mungeTableName);
            }
        }

        /**
         * @param Group $group
         */
        public static function groupAddedToGroup(Group $group)
        {
            self::groupAddedOrRemovedFromGroup(true, $group);
        }

        /**
         * @param Group $group
         */
        public static function groupBeingRemovedFromGroup(Group $group) // Call being methods before the destructive operation.
        {
            self::groupAddedOrRemovedFromGroup(false, $group);
        }

        /**
         * @param $group
         */
        public static function groupBeingDeleted($group) // Call being methods before the destructive operation.
        {
            if ($group->group->id > 0 && !$group->group->isSame($group)) // Prevent cycles in database auto build.
            {
                self::groupBeingRemovedFromGroup($group);
            }
            foreach ($group->groups as $childGroup)
            {
                if ($group->isSame($childGroup)) // Prevent cycles in database auto build.
                {
                    continue;
                }
                self::groupBeingRemovedFromGroup($childGroup);
            }
            foreach ($group->users as $user)
            {
                self::userRemovedFromGroup($group, $user);
            }
            foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
            {
                $groupId = $group->id;
                $mungeTableName = self::getMungeTableName($modelClassName);
                ZurmoRedBean::exec("delete from $mungeTableName
                     where       munge_id = 'G$groupId'");
            }
        }

        protected static function groupAddedOrRemovedFromGroup($isAdd, Group $group)
        {
            assert('is_bool($isAdd)');
            if ($group->group->isSame($group)) // Prevent cycles in database auto build.
            {
                return;
            }

            $countMethod1 = $isAdd ? 'bulkIncrementCount'             : 'bulkDecrementCount';
            $countMethod2 = $isAdd ? 'bulkIncrementParentRolesCounts' : 'bulkDecrementParentRolesCounts';

            $parentGroups = self::getAllParentGroups($group);
            $users  = self::getAllUsersInGroupAndChildGroupsRecursively($group);

            // Handle groups that $parentGroup is in. In/decrement for the containing groups' containing
            // groups the models they have explicit permissions on.
            // And handle user's role's parents. In/decrement for all users that have permission because
            // they are now in the containing group.
            if (count($parentGroups) > 0)
            {
                $parentGroupPermitableIds = array();
                foreach ($parentGroups as $parentGroup)
                {
                    $parentGroupPermitableIds[] = $parentGroup->getClassId('Permitable');
                }
                $sql = 'select securableitem_id
                        from   permission
                        where  permitable_id in (' . join(', ', $parentGroupPermitableIds) . ')';
                $securableItemIds = ZurmoRedBean::getCol($sql);
                foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
                {
                    $mungeTableName = self::getMungeTableName($modelClassName);
                    self::$countMethod1($mungeTableName, $securableItemIds, $group);
                    foreach ($users as $user)
                    {
                        if ($user->role->id > 0)
                        {
                            self::$countMethod2($mungeTableName, $securableItemIds, $user->role);
                        }
                    }
                }
            }
            if (!$isAdd)
            {
                foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
                {
                    $mungeTableName = self::getMungeTableName($modelClassName);
                    self::garbageCollect($mungeTableName);
                }
            }
        }

        protected static function getAllUsersInGroupAndChildGroupsRecursively(Group $group)
        {
            $users = array();
            foreach ($group->users as $user)
            {
                $users[] = $user;
            }
            foreach ($group->groups as $childGroup)
            {
                if ($group->isSame($childGroup)) // Prevent cycles in database auto build.
                {
                    continue;
                }
                $users = array_merge($users, self::getAllUsersInGroupAndChildGroupsRecursively($childGroup));
            }
            return $users;
        }

        protected static function getAllParentGroups(Group $group)
        {
            $parentGroups = array();
            $parentGroup = $group->group;
            while ($parentGroup->id > 0 && !$parentGroup->isSame($parentGroup->group)) // Prevent cycles in database auto build.
            {
                $parentGroups[] = $parentGroup;
                $parentGroup = $parentGroup->group;
            }
            return $parentGroups;
        }

        // Role operations.

        /**
         * @param Role $role
         */
        public static function roleParentSet(Role $role)
        {
            assert('$role->role->id > 0');
            self::roleParentSetOrRemoved(true, $role);
        }

        /**
         * @param Role $role
         */
        public static function roleParentBeingRemoved(Role $role) // Call being methods before the destructive operation.
        {
            assert('$role->role->id > 0');
            self::roleParentSetOrRemoved(false, $role);
        }

        /**
         * @param Role $role
         */
        public static function roleBeingDeleted(Role $role) // Call being methods before the destructive operation.
        {
            foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
            {
                if ($role->role->id > 0)
                {
                    self::roleParentBeingRemoved($role);
                }
                foreach ($role->roles as $childRole)
                {
                    if ($childRole->role->id > 0)
                    {
                        self::roleParentBeingRemoved($childRole);
                    }
                }
                $mungeTableName = self::getMungeTableName($modelClassName);
                $roleId = $role->id;
                $sql = "delete from $mungeTableName
                        where       munge_id = 'R$roleId'";
                ZurmoRedBean::exec($sql);
            }
        }

        protected static function roleParentSetOrRemoved($isSet, Role $role)
        {
            assert('is_bool($isSet)');
            if ($role->role->isSame($role)) // Prevent cycles in database auto build.
            {
                return;
            }

            $countMethod = $isSet ? 'bulkIncrementParentRolesCounts' : 'bulkDecrementParentRolesCounts';

            foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
            {
                $mungeTableName = self::getMungeTableName($modelClassName);

                $usersInRolesChildren = self::getAllUsersInRolesChildRolesRecursively($role);

                // Handle users in $role. In/decrement for the parent's parent
                // roles the models they either own or have explicit permissions on.

                if (count($role->users) > 0)
                {
                    $userIds      = array();
                    $permitableIds = array();
                    foreach ($role->users as $user)
                    {
                        $userIds[]       = $user->id;
                        $permitableIds[] = $user->getClassId('Permitable');
                    }
                    $sql = 'select securableitem_id
                            from   ownedsecurableitem
                            where  owner__user_id in (' . join(', ', $userIds) . ')
                            union all
                            select securableitem_id
                            from   permission
                            where  permitable_id in (' . join(', ', $permitableIds) . ')';
                    $securableItemIds = ZurmoRedBean::getCol($sql);
                    self::$countMethod($mungeTableName, $securableItemIds, $role->role);
                }

                // Handle users in the child roles of $role. Increment for the parent's parent
                // roles the models they either own or have explicit permissions on.

                if (count($usersInRolesChildren))
                {
                    $userIds       = array();
                    $permitableIds = array();
                    foreach ($usersInRolesChildren as $user)
                    {
                        $userIds[]       = $user->id;
                        $permitableIds[] = $user->getClassId('Permitable');
                    }
                    $sql = 'select securableitem_id
                            from   ownedsecurableitem
                            where  owner__user_id in (' . join(', ', $userIds) . ')
                            union all
                            select securableitem_id
                            from   permission
                            where  permitable_id in (' . join(', ', $permitableIds) . ')';
                    $securableItemIds = ZurmoRedBean::getCol($sql);
                    self::$countMethod($mungeTableName, $securableItemIds, $role);
                }

                // Handle groups for the users in $role. Increment for the parent's parent
                // roles the models they have explicit permissions on.

                if (count($role->users) > 0)
                {
                    $permitableIds = array();
                    foreach ($role->users as $user)
                    {
                        foreach ($user->groups as $group)
                        {
                            $permitableIds[] = $group->getClassId('Permitable');
                        }
                    }
                    $permitableIds = array_unique($permitableIds);
                    $sql = 'select securableitem_id
                            from   permission
                            where  permitable_id in (' . join(', ', $permitableIds) . ')';
                    $securableItemIds = ZurmoRedBean::getCol($sql);
                    self::$countMethod($mungeTableName, $securableItemIds, $role->role);
                }

                // Handle groups for the users $role's child roles. Increment for the role's parent
                // roles the models they have explicit permissions on.

                if (count($usersInRolesChildren))
                {
                    $permitableIds = array();
                    foreach ($usersInRolesChildren as $user)
                    {
                        foreach ($user->groups as $group)
                        {
                            $permitableIds[] = $group->getClassId('Permitable');
                        }
                    }
                    $permitableIds = array_unique($permitableIds);
                    if (count($permitableIds) > 0)
                    {
                        $sql = 'select securableitem_id
                                from   permission
                                where  permitable_id in (' . join(', ', $permitableIds) . ')';
                        $securableItemIds = ZurmoRedBean::getCol($sql);
                    }
                    else
                    {
                        $securableItemIds = array();
                    }
                    self::$countMethod($mungeTableName, $securableItemIds, $role);
                }
                if (!$isSet)
                {
                    self::garbageCollect($mungeTableName);
                }
            }
        }

        protected static function getAllUsersInRolesChildRolesRecursively(Role $role)
        {
            $users = array();
            foreach ($role->roles as $childRole)
            {
                if ($role->isSame($childRole)) // Prevent cycles in database auto build.
                {
                    continue;
                }
                foreach ($childRole->users as $user)
                {
                    $users[] = $user;
                }
                $users = array_merge($users, self::getAllUsersInRolesChildRolesRecursively($childRole));
            }
            return $users;
        }

        /**
         * @param User $user
         */
        public static function userAddedToRole(User $user)
        {
            assert('$user->role->id > 0');
            foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
            {
                $mungeTableName = self::getMungeTableName($modelClassName);
                $userId = $user->id;
                $sql = "select securableitem_id
                        from   ownedsecurableitem
                        where  owner__user_id = $userId";
                $securableItemIds = ZurmoRedBean::getCol($sql);
                //Increment the parent roles for securableItems that the user is the owner on.
                self::bulkIncrementParentRolesCounts($mungeTableName, $securableItemIds, $user->role);

                //Get all downstream groups the user is in including any groups that are in those groups recursively.
                //Then for each group found, add weight for the user's upstream roles.
                $groupMungeIds = array();
                foreach ($user->groups as $group)
                {
                    $groupMungeIds[] = 'G' . $group->id;
                    self::getAllUpstreamGroupsRecursively($group, $groupMungeIds);
                }
                if (count($groupMungeIds) > 0)
                {
                    $inSqlPart = SQLOperatorUtil::resolveOperatorAndValueForOneOf('oneOf', $groupMungeIds, true);
                    $sql = "select distinct $mungeTableName.securableitem_id
                            from   $mungeTableName
                            where  $mungeTableName.munge_id $inSqlPart";
                    $securableItemIds = ZurmoRedBean::getCol($sql);
                    self::bulkIncrementParentRolesCounts($mungeTableName, $securableItemIds, $user->role);
                }
            }
        }

        /**
         * @param User $user
         * @param Role $role
         */
        public static function userBeingRemovedFromRole(User $user, Role $role)
        {
            foreach (PathUtil::getAllMungableModelClassNames() as $modelClassName)
            {
                $mungeTableName = self::getMungeTableName($modelClassName);
                $userId = $user->id;
                $sql = "select securableitem_id
                        from   ownedsecurableitem
                        where  owner__user_id = $userId";
                $securableItemIds = ZurmoRedBean::getCol($sql);
                self::bulkDecrementParentRolesCounts($mungeTableName, $securableItemIds, $role);

                $sql = "select $mungeTableName.securableitem_id
                        from   $mungeTableName, _group__user
                        where  $mungeTableName.munge_id = concat('G', _group__user._group_id) and
                               _group__user._user_id = $userId";
                $securableItemIds = ZurmoRedBean::getCol($sql);
                self::bulkDecrementParentRolesCounts($mungeTableName, $securableItemIds, $role);
                /*
                 * This additional step I don't think is needed because the sql query above actually traps
                 * the upstream explicit securableItems because the lower level groups will already have a point for
                 * each of them.
                    What groups are the user part of and what groups are those groups children of recursively?
                    For any models that have that group explicity for read, subtract 1 point for the user's
                    upstream roles from the disconnected role.
                */
                self::garbageCollect($mungeTableName);
            }
        }

        ///////////////////////////////////////////////////////////////////////

        /**
         * @param Group $group
         * @param array $groupMungeIds
         */
        public static function getAllUpstreamGroupsRecursively(Group $group, & $groupMungeIds)
        {
            assert('is_array($groupMungeIds)');
            if ($group->group->id > 0 )
            {
                $groupMungeIds[] = 'G' . $group->group->id;
                if ($group->isSame($group->group))
                {
                    //Do Nothing. Prevent cycles in database auto build.
                }
                else
                {
                    self::getAllUpstreamGroupsRecursively($group->group, $groupMungeIds);
                }
            }
        }

        /**
         * @param User $user
         * @return array
         */
        public static function getUserRoleIdAndGroupIds(User $user)
        {
            if ($user->role->id > 0)
            {
                $roleId = $user->role->id;
            }
            else
            {
                $roleId = null;
            }
            $groupIds = array();
            foreach ($user->groups as $group)
            {
                $groupIds[] = $group->id;
            }
            return array($roleId, $groupIds);
        }

        /**
         * @param User $user
         * @return array
         */
        public static function getMungeIdsByUser(User $user)
        {
            list($roleId, $groupIds) = self::getUserRoleIdAndGroupIds($user);
            $mungeIds = array("U$user->id");
            if ($roleId != null)
            {
                $mungeIds[] = "R$roleId";
            }
            foreach ($groupIds as $groupId)
            {
                $mungeIds[] = "G$groupId";
            }
            //Add everyone group
            $everyoneGroupId = Group::getByName(Group::EVERYONE_GROUP_NAME)->id;
            if (!in_array("G" . $everyoneGroupId, $mungeIds) && $everyoneGroupId > 0)
            {
                $mungeIds[] = "G" . $everyoneGroupId;
            }
            return $mungeIds;
        }

        /**
         * Public for testing only. Need to manually create test model tables that would not be picked up normally.
         */
        public static function recreateTable($mungeTableName)
        {
            assert('is_string($mungeTableName) && $mungeTableName  != ""');
            ZurmoRedBean::$writer->dropTableByTableName($mungeTableName);
            $schema = static::getMungeTableSchemaByName($mungeTableName);
            CreateOrUpdateExistingTableFromSchemaDefinitionArrayUtil::generateOrUpdateTableBySchemaDefinition(
                                                                                        $schema, new MessageLogger());
        }

        protected static function getMungeTableSchemaByName($tableName)
        {
            return array($tableName =>  array('columns' => array(
                                                    array(
                                                        'name' => 'securableitem_id',
                                                        'type' => 'INT(11)',
                                                        'unsigned' => 'UNSIGNED',
                                                        'notNull' => 'NOT NULL', // Not Coding Standard
                                                        'collation' => null,
                                                        'default' => null,
                                                    ),
                                                    array(
                                                        'name' => 'munge_id',
                                                        'type' => 'VARCHAR(12)',
                                                        'unsigned' => null,
                                                        'notNull' => 'NOT NULL', // Not Coding Standard
                                                        'collation' => 'COLLATE utf8_unicode_ci',
                                                        'default' => null,
                                                    ),
                                                    array(
                                                        'name' => 'count',
                                                        'type' => 'INT(8)',
                                                        'unsigned' => 'UNSIGNED',
                                                        'notNull' => 'NOT NULL', // Not Coding Standard
                                                        'collation' => null,
                                                        'default' => null,
                                                    ),
                                                ),
                                            'indexes' => array('securableitem_id_munge_id' => array(
                                                                'columns' => array('securableitem_id', 'munge_id'),
                                                                'unique' => true,
                                                            ),
                                                            $tableName . '_securableitem_id' => array(
                                                                'columns' => array('securableitem_id'),
                                                                'unique' => false,
                                                            ),
                                                    ),
                                            )
                                        );
        }

        protected static function incrementCount($mungeTableName, $securableItemId, $item)
        {
            assert('is_string($mungeTableName) && $mungeTableName != ""');
            assert('is_int($securableItemId) && $securableItemId > 0');
            assert('$item instanceof User || $item instanceof Group || $item instanceof Role');
            $itemId  = $item->id;
            $type    = self::getMungeType($item);
            $mungeId = "$type$itemId";
            ZurmoRedBean::exec("insert into $mungeTableName
                                 (securableitem_id, munge_id, count)
                                 values ($securableItemId, '$mungeId', 1)
                                 on duplicate key
                                 update count = count + 1");
        }

        protected static function setCount($mungeTableName, $securableItemId, $item, $count)
        {
            assert('is_string($mungeTableName) && $mungeTableName != ""');
            assert('is_int($securableItemId) && $securableItemId > 0');
            assert('$item instanceof User || $item instanceof Group || $item instanceof Role');
            $itemId  = $item->id;
            $type    = self::getMungeType($item);
            $mungeId = "$type$itemId";
            ZurmoRedBean::exec("insert into $mungeTableName
                                 (securableitem_id, munge_id, count)
                                 values ($securableItemId, '$mungeId', $count)
                                 on duplicate key
                                 update count = $count");
        }

        protected static function decrementCount($mungeTableName, $securableItemId, $item)
        {
            assert('is_string($mungeTableName) && $mungeTableName != ""');
            assert('is_int($securableItemId) && $securableItemId > 0');
            assert('$item instanceof User || $item instanceof Group || $item instanceof Role');
            $itemId  = $item->id;
            $type    = self::getMungeType($item);
            $mungeId = "$type$itemId";
            ZurmoRedBean::exec("update $mungeTableName
                                 set count = count - 1
                                 where securableitem_id = $securableItemId and
                                 munge_id         = '$mungeId'");
        }

        protected static function decrementCountForAllSecurableItems($mungeTableName, $item)
        {
            assert('is_string($mungeTableName) && $mungeTableName != ""');
            assert('$item instanceof User || $item instanceof Group || $item instanceof Role');
            $itemId  = $item->id;
            $type    = self::getMungeType($item);
            $mungeId = "$type$itemId";
            ZurmoRedBean::exec("update $mungeTableName
                                 set count = count - 1
                                 where munge_id = '$mungeId'");
        }

        protected static function bulkIncrementCount($mungeTableName, $securableItemIds, $item)
        {
            assert('is_string($mungeTableName) && $mungeTableName != ""');
            assert('$item instanceof User || $item instanceof Group || $item instanceof Role');
            foreach ($securableItemIds as $securableItemId)
            {
                self::incrementCount($mungeTableName, intval($securableItemId), $item);
            }
        }

        protected static function bulkDecrementCount($mungeTableName, $securableItemIds, $item)
        {
            assert('is_string($mungeTableName) && $mungeTableName != ""');
            assert('$item instanceof User || $item instanceof Group || $item instanceof Role');
            foreach ($securableItemIds as $securableItemId)
            {
                self::decrementCount($mungeTableName, intval($securableItemId), $item);
            }
        }

        protected static function incrementParentRolesCounts($mungeTableName, $securableItemId, Role $role)
        {
            assert('is_string($mungeTableName) && $mungeTableName != ""');
            assert('is_int($securableItemId) && $securableItemId > 0');
            if ($role->role->isSame($role)) // Prevent cycles in database auto build.
            {
                return;
            }
            if ($role->role->id > 0)
            {
                self::incrementCount            ($mungeTableName, $securableItemId, $role->role);
                self::incrementParentRolesCounts($mungeTableName, $securableItemId, $role->role);
            }
        }

        protected static function decrementParentRolesCounts($mungeTableName, $securableItemId, Role $role)
        {
            assert('is_string($mungeTableName) && $mungeTableName != ""');
            assert('is_int($securableItemId) && $securableItemId > 0');
            if ($role->role->isSame($role)) // Prevent cycles in database auto build.
            {
                return;
            }
            if ($role->role->id > 0)
            {
                self::decrementCount            ($mungeTableName, $securableItemId, $role->role);
                self::decrementParentRolesCounts($mungeTableName, $securableItemId, $role->role);
            }
        }

        protected static function decrementParentRolesCountsForAllSecurableItems($mungeTableName, Role $role)
        {
            assert('is_string($mungeTableName) && $mungeTableName != ""');
            if ($role->role->isSame($role)) // Prevent cycles in database auto build.
            {
                return;
            }
            if ($role->role->id > 0)
            {
                self::decrementCountForAllSecurableItems            ($mungeTableName, $role->role);
                self::decrementParentRolesCountsForAllSecurableItems($mungeTableName, $role->role);
            }
        }

        protected static function bulkIncrementParentRolesCounts($mungeTableName, $securableItemIds, Role $role)
        {
            foreach ($securableItemIds as $securableItemId)
            {
                self::incrementParentRolesCounts($mungeTableName, intval($securableItemId), $role);
            }
        }

        protected static function bulkDecrementParentRolesCounts($mungeTableName, $securableItemIds, Role $role)
        {
            foreach ($securableItemIds as $securableItemId)
            {
                self::decrementParentRolesCounts($mungeTableName, intval($securableItemId), $role);
            }
        }

        // This must be called ny any public method which decrements
        // counts after it has done all its count decrementing.
        // It is not done in decrementCount to avoid doing it more
        // than is necessary.
        protected static function garbageCollect($mungeTableName)
        {
            assert("(int)ZurmoRedBean::getCell('select count(*)
                                from   $mungeTableName
                                where  count < 0') == 0");
            ZurmoRedBean::exec("delete from $mungeTableName
                     where       count = 0");
            assert("(int)ZurmoRedBean::getCell('select count(*)
                                from   $mungeTableName
                                where  count < 1') == 0");
        }

        protected static function getMungeType($item)
        {
            assert('$item instanceof User || $item instanceof Group || $item instanceof Role');
            return substr(get_class($item), 0, 1);
        }

        protected static function getMainTableName($modelClassName)
        {
            assert('is_string($modelClassName) && $modelClassName != ""');
            return RedBeanModel::getTableName($modelClassName);
        }

        /**
         * @param $modelClassName
         * @return string
         */
        public static function getMungeTableName($modelClassName)
        {
            assert('is_string($modelClassName) && $modelClassName != ""');
            return self::getMainTableName($modelClassName) . '_read';
        }
    }
?>
