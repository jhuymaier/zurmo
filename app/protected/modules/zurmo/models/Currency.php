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
     * Model for storing system supported currencies.
     */
    class Currency extends RedBeanModel
    {
        /**
         * $currencyIdRowsByCode, @see $cachedCurrencyIdByCode, @see $cachedCurrencyById, and @see $allCachedCurrencies
         * are all part of an effort to provide php level caching for currency.  Currency rarely changes, yet since it
         * is attached to currencyValue as related model, it can get accessed quite a bit especially if you have many
         * currencyValue models related to another model via custom attributes for example.  Eventually the caching
         * mechanism here needs to be moved into something using memcache and combined so we don't have 4 different
         * properties, but for now this was implemented to reduce save query hits to the database.
         * @var array
         */
        private static $currencyIdRowsByCode   = array();

        private static $cachedCurrencyIdByCode = array();

        private static $cachedCurrencyById     = array();

        private static $allCachedCurrencies    = array();

        /**
         * Since currency rarely changes, there is no reason to attempt to save it when a @see CurrencyValue is
         * created or changed, for example.  Currency can only be saved on its own directly and not through a related
         * model.
         * @var boolean
         */
        protected $isSavableFromRelation       = false;

        /**
         * Override of getById in RedBeanModel in order to cache the result.  Currency rarely  changes, so caching
         * the currency on this method provides a performance boost.
         * @param integer $id
         * @param string $modelClassName
         */
        public static function getById($id, $modelClassName = null)
        {
            assert('$modelClassName == "Currency" || $modelClassName == null');
            if (isset(self::$cachedCurrencyById[$id]))
            {
                return self::$cachedCurrencyById[$id];
            }
            $currency = parent::getById($id, $modelClassName);
            self::$cachedCurrencyById[$id] = $currency;
            return $currency;
        }

        /**
         * Gets a currency by code.
         * @param $code String Code.
         * @return A model of type currency
         */
        public static function getByCode($code)
        {
            assert('is_string($code)');
            $tableName = self::getTableName('Currency');
            $beans = ZurmoRedBean::find($tableName, "code = '$code'");
            assert('count($beans) <= 1');
            if (count($beans) == 0)
            {
                throw new NotFoundException();
            }
            return static::makeModel(end($beans), 'Currency');
        }

        /**
         * Override to check if no results are returned and load the baseCurrency as the first currency in that
         * scenario.
         */
        public static function getAll($orderBy = null, $sortDescending = false,
                                        $modelClassName = null, $buildFirstCurrency = true)
        {
            $currencies = parent::getAll($orderBy, $sortDescending, $modelClassName);
            if (count($currencies) > 0 || $buildFirstCurrency == false)
            {
                return $currencies;
            }

            return array(self::makeBaseCurrency());
        }

        public static function makeBaseCurrency()
        {
            $currency             = new Currency();
            $currency->code       = Yii::app()->currencyHelper->getBaseCode();
            $currency->rateToBase = 1;
            $saved                = $currency->save();
            if (!$saved)
            {
                throw new NotSupportedException();
            }
            return $currency;
        }

        public function __toString()
        {
            if (trim($this->code) == '')
            {
                return Zurmo::t('Core', '(None)');
            }
            return $this->code;
        }

        public static function canSaveMetadata()
        {
            return true;
        }

        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'active',
                    'code',
                    'rateToBase',
                ),
                'rules' => array(
                    array('active',     'boolean'),
                    array('active',     'default', 'value' => true),
                    array('code',       'required'),
                    array('code',       'unique'),
                    array('code',       'type', 'type' => 'string'),
                    array('code',       'length', 'min' => 3, 'max' => 3),
                    array('code',       'match',  'pattern' => '/^[A-Z][A-Z][A-Z]$/', // Not Coding Standard
                                                  'message' => 'Code must be a valid currency code.'),
                    array('rateToBase', 'required'),
                    array('rateToBase', 'type', 'type' => 'float'),
                ),
                'lastAttemptedRateUpdateTimeStamp'      => null,
            );
            return $metadata;
        }

        public static function isTypeDeletable()
        {
            return true;
        }

        /**
         * Attempt to get the cached currency model by providing a currency code.
         * @param string $code
         * @return Currency model if found, otherwise returns null
         */
        public static function getCachedCurrencyByCode($code)
        {
            assert('is_string($code)');
            if (isset(self::$cachedCurrencyIdByCode[$code]) &&
               self::$cachedCurrencyById[self::$cachedCurrencyIdByCode[$code]])
               {
                    return self::$cachedCurrencyById[self::$cachedCurrencyIdByCode[$code]];
               }
               return null;
        }

        /**
         * Given a currency model, set the currency model as cached.
         * @param unknown_type $currency
         */
        public static function setCachedCurrency(Currency $currency)
        {
            assert('$currency->id > 0');
            self::$cachedCurrencyIdByCode[$currency->code]     = $currency->id;
            self::$cachedCurrencyById[$currency->id]           = $currency;
        }

        /**
         * Override to provide a performance boost by relying on cached row data regarding uniqueness of a currency.
         * This was required since the currency validator was running anytime a currencyValue is validated. Yet we don't
         * need to check the validation of currency all the time since currency does not change often and not from a
         * related model.
         * (non-PHPdoc)
         * @see RedBeanModel::isUniqueAttributeValue()
         */
        public function isUniqueAttributeValue($attributeName, $value)
        {
            if ($attributeName != 'code')
            {
                return parent::isUniqueAttributeValue($attributeName, $value);
            }
            assert('$value !== null');
            if (isset(static::$currencyIdRowsByCode[$value]))
            {
                $rows = static::$currencyIdRowsByCode[$value];
            }
            else
            {
                $modelClassName = $this->attributeNameToBeanAndClassName[$attributeName][1];
                $tableName = self::getTableName($modelClassName);
                $rows = ZurmoRedBean::getAll('select id from ' . $tableName . " where $attributeName = ?", array($value));
                static::$currencyIdRowsByCode[$value] = $rows;
            }
            return count($rows) == 0 || count($rows) == 1 && $rows[0]['id'] == $this->id;
        }

        /**
         * Override to resetCache after a currency is saved.
         * (non-PHPdoc)
         * @see RedBeanModel::save()
         */
        public function save($runValidation = true, array $attributeNames = null)
        {
            $saved = parent::save($runValidation, $attributeNames);
            self::resetCaches();
            return $saved;
        }

        /**
         * Attempt to get the cached list of currency models.
         * @return array of Currency models if found, otherwise returns null
         */
        public static function getAllCachedCurrencies()
        {
            if (empty(self::$allCachedCurrencies))
            {
                return null;
            }
            return self::$allCachedCurrencies;
        }

        /**
         * Set a list of cached currency models
         * @param $currencies
         */
        public static function setAllCachedCurrencies($currencies)
        {
            self::$allCachedCurrencies = $currencies;
        }

        /**
         * The currency cache is a php only cache.  This resets the cache.  Useful during testing when the php
         * is still running between requests and tests.
         */
        public static function resetCaches()
        {
            self::$currencyIdRowsByCode   = array();
            self::$cachedCurrencyIdByCode = array();
            self::$cachedCurrencyById     = array();
            self::$allCachedCurrencies    = array();
        }

        protected static function translatedAttributeLabels($language)
        {
            return array_merge(parent::translatedAttributeLabels($language),
                array(
                    'active'        => Zurmo::t('Core', 'Active',        array(), null, $language),
                    'code'          => Zurmo::t('ZurmoModule', 'Code',          array(), null, $language),
                    'rateToBase'    => Zurmo::t('ZurmoModule', 'Rate To Base',  array(), null, $language),
                )
            );
        }
    }
?>