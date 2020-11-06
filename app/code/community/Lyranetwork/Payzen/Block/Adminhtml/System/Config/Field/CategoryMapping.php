<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Custom renderer for the category mapping field.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_CategoryMapping
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn(
            'magento_category',
            array(
                'label' => Mage::helper('payzen')->__('Magento category'),
                'style' => 'width: 200px;',
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_Label
            )
        );

        $options = array('options' => Mage::helper('payzen')->getConfigArray('product_categories'));;
        $this->addColumn(
            'payzen_category',
            array(
                'label' => Mage::helper('payzen')->__('PayZen category'),
                'style' => 'width: 200px;',
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_List($options)
            )
        );

        $this->_addAfter = false;

        parent::__construct();

        $this->setTemplate('payzen/field/array.phtml');
    }

    /**
     * Obtain existing data from form element
     *
     * Each row will be instance of Varien_Object
     *
     * @return array
     */
    public function getArrayRows()
    {
        $value = array();

        /**
         * @var array[string][string] $categories
         */
        $categories = $this->_getAllCategories();

        $savedCategories = $this->getElement()->getValue();
        if ($savedCategories && is_array($savedCategories) && ! empty($savedCategories)) {
            foreach ($savedCategories as $id => $category) {
                if (key_exists($category['code'], $categories)) {
                    // Add category current name.
                    $category['magento_category'] = $categories[$category['code']];
                    $value[$id] = $category;

                    unset($categories[$category['code']]);
                }
            }
        }

        // Add not saved yet categories.
        if ($categories && is_array($categories) && ! empty($categories)) {
            foreach ($categories as $code => $name) {
                $value[uniqid('_' . $code . '_')] = array(
                        'code' => $code,
                        'magento_category' => $name,
                        'payzen_category' => 'FOOD_AND_GROCERY',
                        'mark' => '*'
                );
            }
        }

        $this->getElement()->setValue($value);
        return parent::getArrayRows();
    }

    protected function _getAllCategories()
    {
        $options = array();

        $categories = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('id')
            ->addIsActiveFilter();

        $storeId = Mage::getSingleton('adminhtml/config_data')->getStore();
        $rootId = $storeId ? Mage::app()->getStore($storeId)->getRootCategoryId() : null;
        if ($rootId) {
            $categories = $categories->addPathFilter("^1/$rootId/[0-9]+$");
        } else {
            $categories = $categories->addPathFilter("^1/[0-9]+/[0-9]+$");
        }

        foreach ($categories as $category) {
            $options[$category->getId()] = $category->getName();
        }

        return $options;
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $script = '<script type="text/javascript">
                document.observe("dom:loaded", function() {
                    $$("select.payzen_list_payzen_category").each(function(elt) {
                        var value = elt.readAttribute("currentvalue");

                        // Option to select.
                        var opt = elt.select("option[value=\"" + value + "\"]");
                        if (opt && opt.length > 0) {
                            opt[0].selected = true;
                        }
                    });';

        if ($this->getElement()->getCanUseWebsiteValue() || $this->getElement()->getCanUseDefaultValue()) {
            $script .= '
                    Event.observe($("payment_payzen_common_category"), "change", function() {
                        toggleValueElements(
                            $("payment_payzen_category_mapping_inherit"),
                            $("payment_payzen_category_mapping").parentNode
                        );
                    });';
        }

        $script .= '
                });
                </script>';

        return parent::_toHtml() . "\n" . $script;
    }
}
