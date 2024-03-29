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

    class DropDownAttributeForm extends AttributeForm implements CollectionAttributeFormInterface
    {
        public $customFieldDataName;

        public $customFieldDataData;

        /**
         * Array contains translated labels for the drop down names, but does not include the base language translation as
         * this is considered to be the drop down name itself and has no translation.
         * @var array
         */
        public $customFieldDataLabels;

        /**
         * Used when changing the value of an existing data item.  Coming in from a post, this array will have the
         * old values that can be used to compare against and update the new values accordingly based on any changes.
         */
        public $customFieldDataDataExistingValues;

        /**
         * CustomFieldData model id.
         */
        protected $customFieldDataId;

        public $defaultValueOrder;

        /**
         * Override needed to translate defaultValue to the order.  Order corresponds to the keyed index of the
         * customFieldDataData array.  This is needed for the form to operate correctly in the user interface.
         * Otherwise if you select a default as a new pick list item, the user interface has no way of posting
         * the correct Id for the defaultValue since the new pick list item has not been created yet.
         * Also need override to properly adapt pick list items.
         */
        public function __construct(RedBeanModel $model = null, $attributeName = null)
        {
            parent::__construct($model, $attributeName);
            if ($model !== null)
            {
                $this->customFieldDataName = $model->$attributeName->data->name;
                $this->customFieldDataId   = $model->$attributeName->data->id;
                $this->customFieldDataData = unserialize($model->$attributeName->data->serializedData);
                if ($model->$attributeName->data->serializedLabels !== null)
                {
                    $this->customFieldDataLabels = unserialize($model->$attributeName->data->serializedLabels);
                }
                $this->defaultValueOrder   = DropDownDefaultValueOrderUtil::getDefaultValueOrderFromDefaultValue(
                                                $this->defaultValue, $this->customFieldDataData);
            }
        }

        /**
         * Get how many records in a model have each possible customFieldData value selected.
         * If the customFieldData doesn't exist yet, then return 0.
         */
        public function getCollectionCountData()
        {
            if ($this->customFieldDataId > 0)
            {
                return GroupedAttributeCountUtil::getCountData('CustomField', 'value', 'data',
                                                               $this->customFieldDataId);
            }
            return 0;
        }

        public function getCustomFieldDataId()
        {
            return $this->customFieldDataId;
        }

        public function rules()
        {
            return array_merge(parent::rules(), array(
                array('customFieldDataData',                'safe'),
                array('customFieldDataDataExistingValues',  'safe'),
                array('customFieldDataData',                'required',
                                                            'message' => 'You must have at least one pick list item.'),
                array('customFieldDataData',                'validateCustomFieldDataData'),
                array('defaultValueOrder',                  'safe'),
                array('attributeName',                      'length', 'min'  => 1, 'max' => 64),
                array('customFieldDataLabels',              'safe'),
                ));
        }

        public function attributeLabels()
        {
            return array_merge(parent::attributeLabels(), array(
                'customFieldDataData'   => Zurmo::t('DesignerModule', 'Pick List Values'),
                'customFieldDataLabels' => Zurmo::t('DesignerModule', 'Pick List Value Translations'),
                'defaultValueOrder'     => Zurmo::t('ZurmoModule', 'Default Value'),
            ));
        }

        public static function getAttributeTypeDisplayName()
        {
            return Zurmo::t('DesignerModule', 'Pick List');
        }

        public static function getAttributeTypeDisplayDescription()
        {
            return Zurmo::t('DesignerModule', 'A pick list with specific values to select from');
        }

        public function getAttributeTypeName()
        {
            return 'DropDown';
        }

        /**
         * @see AttributeForm::getModelAttributeAdapterNameForSavingAttributeFormData()
         */
        public static function getModelAttributeAdapterNameForSavingAttributeFormData()
        {
            return 'DropDownModelAttributesAdapter';
        }

        /**
         * Test if there are two picklist values with the same name.  This is not allowed. Also make sure there is
         * no comma in the value string.
         */
        public function validateCustomFieldDataData($attribute, $params)
        {
            $data = $this->$attribute;
            if (!empty($data) && $nonUniqueData = array_diff_key( $data , ArrayUtil::array_iunique( $data )) )
            {
                $nonUniqueValuesString = null;
                foreach ($nonUniqueData as $nonUniqueValue)
                {
                    if ($nonUniqueValuesString != null)
                    {
                       $nonUniqueValuesString .= ', ';
                    }
                    $nonUniqueValuesString .= $nonUniqueValue;
                }
                $this->addError('customFieldDataData',
                Zurmo::t('DesignerModule',
                'Each item must be uniquely named and the following are not: {values}',
                array('{values}' => $nonUniqueValuesString)));
            }
            if (!empty($data))
            {
                foreach ($data as $value)
                {
                    if ($value != str_replace(',', '', $value)) // Not Coding Standard
                    {
                        $this->addError('customFieldDataData', Zurmo::t('DesignerModule', 'Each value must not contain a comma.'));
                        return;
                    }
                    elseif ($value == '')
                    {
                        $this->addError('customFieldDataData', Zurmo::t('Core', 'Value cannot be blank.'));
                        return;
                    }
                }
            }
        }

        /**
         * Override to handle defaultValueOrder since the attributePropertyToDesignerFormAdapter does not specifically
         * support this property.
         */
        public function canUpdateAttributeProperty($propertyName)
        {
            if ($propertyName == 'defaultValueOrder')
            {
                return true;
            }
            return $this->attributePropertyToDesignerFormAdapter->canUpdateProperty($propertyName);
        }

        /**
         * How many attributes that are drop downs across the different models are using this customFieldData.
         */
        public function getModelPluralNameAndAttributeLabelsThatUseCollectionData()
        {
            return CustomFieldDataModelUtil::getModelPluralNameAndAttributeLabelsByName($this->customFieldDataName);
        }

        /**
         * Override to handle the case when the attributeName was already created used in another module
         */
        public function validateAttributeNameDoesNotExists()
        {
            parent::validateAttributeNameDoesNotExists();
            if (CustomFieldData::getByName($this->attributeName, false)->id > 0)
            {
                $this->addError('attributeName', Zurmo::t('DesignerModule', 'A field with this name and data is already used in another module.'));
            }
        }
    }
?>