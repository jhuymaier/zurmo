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
     * TODO
     */
    abstract class Module extends CWebModule
    {
        private $isEnabled = true;

        /**
         * Returns an array of module objects, keyed by module id.
         * Should be called getModules, but the badly named Yii
         * method getModules(), which whould be called something
         * like getModuleConfigurations(), is in the way.
         * @see getModuleNames()
         */
        public static function getModuleObjects()
        {
            $cacheKey   = 'application.allModules';
            try
            {
                // not using default value to save cpu cycles on requests that follow the first exception.
                $modules    = GeneralCache::getEntry($cacheKey);
            }
            catch (NotFoundException $e)
            {
                $moduleConfig = Yii::app()->getModules();
                $modules = array();
                foreach ($moduleConfig as $moduleName => $info)
                {
                     $module = Yii::app()->findModule($moduleName);
                     if (isset($info['modules']) && is_array($info['modules']))
                     {
                        foreach ($info['modules'] as $nestedModuleName => $nestedInfo)
                        {
                            $modules[$nestedModuleName] = $module->getModule($nestedModuleName);
                        }
                     }
                     $modules[$moduleName] = $module;
                }
                GeneralCache::cacheEntry($cacheKey, $modules);
            }
            return $modules;
        }

        /**
         * Returns an array which is the flattened dependencies
         * for given module.
         */
        public static function getDependenciesForModule(Module $module, $dependencies = array())
        {
            assert('$module !== null');
            $dependencies = array_merge(array($module->getName()), $dependencies);
            $dependencyNames = $module->getDependencies();
            foreach ($dependencyNames as $dependencyName)
            {
                if (!in_array($dependencyName, $dependencies))
                {
                    $dependentModule = Yii::app()->findModule($dependencyName);
                    assert('$dependentModule instanceof Module');
                    $dependencies = array_merge(self::getDependenciesForModule($dependentModule, $dependencies),
                                                $dependencies);
                }
            }
            return array_values(array_unique($dependencies));
        }

        /**
         * @returns the name of this module, which is the
         * name of the module's directory under protected/modules.
         */
        public function getName()
        {
            $calledClassName = get_called_class();
            return $calledClassName::getDirectoryName();
        }

        /**
         * @returns the name of this module, which is the
         * name of the module's directory under protected/modules.
         * Same method as getName, but getName cannot be static.
         * @see Module::getName
         */
        public static function getDirectoryName()
        {
            $name = get_called_class();
            $name = substr($name, 0, strlen($name) - strlen('Module'));
            $name = lcfirst($name);
            return $name;
        }

        /**
         * @returns the singular name of the module for example 'Account'
         * keeping the uppercase letters.  Override if the module's pluralized
         * name cannot be changed to singular by simply removing the end 's'.
         */
        public static function getSingularCamelCasedName()
        {
            $name = get_called_class();
            $name = substr($name, 0, strlen($name) - strlen('Module') - 1);
            return $name;
        }

        /**
         * @returns the plural name of the module for example 'Accounts'
         * keeping the uppercase letters.
         */
        public static function getPluralCamelCasedName()
        {
            $name = get_called_class();
            $name = substr($name, 0, strlen($name) - strlen('Module'));
            return $name;
        }

        /**
         * Override in all children modules to ensure proper availability of module labels in the translation system.
         * If the override is not available, then it will generate a label as a last resort.
         * @param string $language
         * @return string - singular module label.
         */
        protected static function getSingularModuleLabel($language)
        {
            $name = static::getPluralModuleLabel($language);
            $name = substr($name, 0, strlen($name) - 1);
            return Zurmo::t('Core', $name, array(), null, $language);
        }

        /**
         * Override in all children modules to ensure proper availability of module labels in the translation system
         * If the override is not available, then it will generate a label as a last resort.
         * @param string $language
         * @return string - plural module label
         */
        protected static function getPluralModuleLabel($language)
        {
            $calledClassName = get_called_class();
            $name = $calledClassName::getDirectoryName();
            $name = preg_replace('/([A-Z])/', ' \1', $name);
            return Zurmo::t('Core', ucfirst($name), array(), null, $language);
        }

        public static function getModuleLabelByTypeAndLanguage($type, $language = null)
        {
            assert('in_array($type, array("Singular", "SingularLowerCase", "Plural", "PluralLowerCase"))');
            assert('$language == null || is_string($language)');
            if ($language == null)
            {
                $language = Yii::app()->language;
            }
            $label = self::getCustomModuleLabelByTypeAndLanguage($type, $language);
            if ($label!= null)
            {
                return $label;
            }
            switch ($type)
            {
                case 'Singular':
                    return static::getSingularModuleLabel($language);
                case 'SingularLowerCase':
                    $string  = static::getSingularModuleLabel($language);
                    return TextUtil::strToLowerWithDefaultEncoding($string);
                case 'Plural':
                    return static::getPluralModuleLabel($language);
                case 'PluralLowerCase':
                    $string  = static::getPluralModuleLabel($language);
                    return TextUtil::strToLowerWithDefaultEncoding($string);
            }
        }

        protected static function getCustomModuleLabelByTypeAndLanguage($type, $language)
        {
            assert('in_array($type, array("Singular", "SingularLowerCase", "Plural", "PluralLowerCase"))');
            assert('$language != null');
            $metadata = static::getMetadata();
            switch ($type)
            {
                case 'Singular':
                    if (isset($metadata['global']['singularModuleLabels']) &&
                        isset($metadata['global']['singularModuleLabels'][$language]))
                    {
                        $label = $metadata['global']['singularModuleLabels'][$language];
                        return preg_match('/^[a-z]/', $label) ? ucwords($label) : $label;
                    }
                case 'SingularLowerCase':
                    if ( isset($metadata['global']['singularModuleLabels']) &&
                        isset($metadata['global']['singularModuleLabels'][$language]))
                    {
                        return $metadata['global']['singularModuleLabels'][$language];
                    }
                case 'Plural':
                    if ( isset($metadata['global']['pluralModuleLabels']) &&
                        isset($metadata['global']['pluralModuleLabels'][$language]))
                    {
                        $label = $metadata['global']['pluralModuleLabels'][$language];
                        return preg_match('/^[a-z]/', $label) ? ucwords($label) : $label;
                    }
                case 'PluralLowerCase':
                    if ( isset($metadata['global']['pluralModuleLabels']) &&
                        isset($metadata['global']['pluralModuleLabels'][$language]))
                    {
                        return $metadata['global']['pluralModuleLabels'][$language];
                    }
            }
        }

        /**
         * Returns whether the module is enabled.
         */
        public function isEnabled()
        {
            return $this->isEnabled;
        }

        /**
         * Returns whether the module is can be disabled. Modules that
         * must not be disabled must override and return false.
         */
        public function canDisable()
        {
            return true;
        }

        /**
         * If setting isEnabled = true then dependent modules which are
         * not enabled will be recursively enabled. If setting
         * isEnabled = false only this module will be disabled.
         */
        public function setIsEnabled($isEnabled)
        {
            assert('is_bool($isEnabled)');
            if (!$isEnabled && !$this->canDisable())
            {
                throw new NotSupportedException();
            }
            if ($isEnabled)
            {
                $modules = Module::GetModuleObjects();
                $dependencies = $this->getDependencies();
                foreach ($dependencies as $dependency)
                {
                    assert('array_key_exists($dependency, $modules)');
                    $modules[$dependency]->setIsEnabled($isEnabled);
                }
            }
            $this->isEnabled = $isEnabled;
            assert('!$this->isEnabled || $this->isEnabled && $this->dependenciesAreEnabled()');
        }

        /**
         * Returns an array of the names of the the
         * modules the module depends on. ie: if the module
         * is enabled then those modules must be too, recursively
         * through their dependencies.
         */
        public abstract function getDependencies();

        /**
         * Returns an array of the dependency modules that are disabled.
         */
        public function getEnabledDependencies($temp = null)
        {
            if ($temp === null) // TODO - remove this $temp junk when the modules metadata is being saved.
            {
                $temp = self::getModuleObjects();
            }
            return $this->getEnabledDependenciesInternal($temp);
        }

        // The public version gets the modules once, then
        // the private version can use it recursively.
        private function getEnabledDependenciesInternal($modules)
        {
            $unsatisfiedDependencies = array();
            $dependencies = $this->getDependencies();
            foreach ($dependencies as $dependency)
            {
                assert('array_key_exists($dependency, $modules)');
                if ($modules[$dependency]->isEnabled())
                {
                    $unsatisfiedDependencies[] = $dependency;
                }
                array_merge($unsatisfiedDependencies, $modules[$dependency]->getEnabledDependencies($modules));
            }
            return $unsatisfiedDependencies;
        }

        public function getRootModelNamesIncludingDependencies()
        {
            $rootModels = $this->getRootModelNames();
            foreach ($this->getEnabledDependencies() as $dependencyName)
            {
                $module = Yii::app()->getModule($dependencyName);
                $dependencyModulesRootModels = $module->getRootModelNamesIncludingDependencies();
                $rootModels = array_merge($rootModels, array_diff($dependencyModulesRootModels, $rootModels));
            }
            return $rootModels;
        }

        /**
         * Implement in all modules that have models. The root models are the
         * models that if they are created, then their related models are
         * created, and so on recursively, then all of module's models will
         * have been created, allowing RedBean to create all of the required
         * tables and columns.
         */
        public function getRootModelNames()
        {
            return array();
        }

        /*
         * Returns the stronger of the two policy values, being the more
         * restrictive given the nature of that specific policy.
         */
        public static function getStrongerPolicy($policyName, array $values)
        {
            throw new NotSupportedException();
        }

        /**
         * @return array of Policy / PolicyRulesType pairings
         */
        public static function getPolicyRulesTypes()
        {
            return array();
        }

        /**
         * TODO
         */
        public function getConfigurationView()
        {
            return new ConfigurationView();
        }

        /**
         * TODO
         */
        public static function getTabMenuItems($user = null)
        {
            assert('$user == null || $user instanceof User');
            $metadata = self::getMetadata();
            if (!empty($metadata['global']['tabMenuItems']))
            {
                return $metadata['global']['tabMenuItems'];
            }
            return array();
        }

        /**
         * TODO
         */
        public static function getAdminTabMenuItems($user = null)
        {
            assert('$user == null || $user instanceof User');
            $metadata = self::getMetadata();
            if (!empty($metadata['global']['adminTabMenuItems']))
            {
                return $metadata['global']['adminTabMenuItems'];
            }
            return array();
        }

        /**
         * TODO
         */
        public static function getConfigureMenuItems()
        {
            $metadata = self::getMetadata();
            if (!empty($metadata['global']['configureMenuItems']))
            {
                return $metadata['global']['configureMenuItems'];
            }
            return array();
        }

        public static function getConfigureSubMenuItems()
        {
            $metadata = self::getMetadata();
            if (!empty($metadata['global']['configureSubMenuItems']))
            {
                return $metadata['global']['configureSubMenuItems'];
            }
            return array();
        }

        public static function getShortCutsCreateMenuItems()
        {
            $calledClass = get_called_class();
            $metadata = $calledClass::getMetadata();
            if (!empty($metadata['global']['shortcutsCreateMenuItems']))
            {
                return $metadata['global']['shortcutsCreateMenuItems'];
            }
            return array();
        }

        public function getDesignerMenuItems()
        {
            $metadata = $this->getMetadata();
            if (!empty($metadata['global']['designerMenuItems']))
            {
                return $metadata['global']['designerMenuItems'];
            }
            return array();
        }

        /**
         * Get the primary model name for a module.
         * Make sure to override in modules that have
         * a Primary model otherwise the method is not
         * supported.
         */
        public static function getPrimaryModelName()
        {
            throw new NotSupportedException();
        }

        /**
         * Override and return a string if the module supports the global search mechanism.
         * @return null if not supported otherwise return the appropriate string.
         */
        public static function getGlobalSearchFormClassName()
        {
            return null;
        }

        /**
         * Override when there is a module that can have module scoping or special search fields in the module
         * list/search view but is not globally searchable.  Activities are an example of this or users.
         */
        public static function modelsAreNeverGloballySearched()
        {
            return false;
        }

        /**
         * Return true if module has any global search attribute names available to search on
         * @return bool
         */
        public static function hasAtLeastOneGlobalSearchAttributeName()
        {
            $metadata = static::getMetadata();
            if ( isset($metadata['global']['globalSearchAttributeNames']) &&
                is_array($metadata['global']['globalSearchAttributeNames']) &&
                count($metadata['global']['globalSearchAttributeNames']) > 0)
            {
                return true;
            }
            return false;
        }

        /**
         * Override and return a string of the StatemetadataAdataper class if the module's primary model supports
         * states.  An example is leads or contacts where the lead is only contacts in a certain state.
         */
        public static function getStateMetadataAdapterClassName()
        {
            return null;
        }

        /**
         * Returns metadata for the module.
         * @see getDefaultMetadata()
         * @param $user The current user.
         * @returns An array of metadata.
         */
        public static function getMetadata(User $user = null)
        {
            $className = get_called_class();
            if ($user == null)
            {
                try
                {
                    // not using default value to save cpu cycles on requests that follow the first exception.
                    return GeneralCache::getEntry($className . 'Metadata');
                }
                catch (NotFoundException $e)
                {
                }
            }
            $metadata = MetadataUtil::getMetadata($className, $user);
            if (YII_DEBUG)
            {
                $className::assertMetadataIsValid($metadata);
            }
            if ($user == null)
            {
                GeneralCache::cacheEntry($className . 'Metadata', $metadata);
            }
            return $metadata;
        }

        /**
         * Sets new metadata.
         * @param $metadata An array of metadata.
         * @param $user The current user.
         */
        public static function setMetadata(array $metadata, User $user = null)
        {
            $className = get_called_class();
            if (YII_DEBUG)
            {
                self::assertMetadataIsValid($metadata);
            }
            MetadataUtil::setMetadata($className, $metadata, $user);
            if ($user == null)
            {
                GeneralCache::forgetEntry($className . 'Metadata');
            }
        }

        /**
         * Returns the default meta data for the class.
         * It must be appended to the meta data
         * from the parent model, if any.
         */
        public static function getDefaultMetadata()
        {
            return array();
        }

        public static function getViewClassNames()
        {
            return static::getAllClassNamesByPathFolder('views');
        }

        /**
         * For a given module, return an array of models that are in that module.
         */
        public static function getModelClassNames()
        {
            return static::getAllClassNamesByPathFolder('models');
        }

        public static function getAllClassNamesByPathFolder($folder)
        {
            assert('is_string($folder)');
            $classNames = array();
            $className = get_called_class();
            $alias      = 'application.modules.' . $className::getDirectoryName() . '.' .  $folder;
            $classNames = PathUtil::getAllClassNamesByPathAlias($alias);
            return $classNames;
        }

        private static function assertMetadataIsValid(array $metadata)
        {
            //add checks as needed.
        }

        /**
         * Override in modules that create default data during an installation.
         */
        public static function getDefaultDataMakerClassName()
        {
        }

        /**
         * Override in modules that create demo data during an installation.
         */
        public static function getDemoDataMakerClassNames()
        {
        }

        /**
         * Override in modules that are reportable in the reporting module
         */
        public static function isReportable()
        {
            return false;
        }

        /**
         * Override in modules that can have workflow rules in the workflow module
         */
        public static function canHaveWorkflow()
        {
            return false;
        }

        /**
         * Override in modules that can be used in content templates
         */
        public static function canHaveContentTemplates()
        {
            return false;
        }
    }
?>
