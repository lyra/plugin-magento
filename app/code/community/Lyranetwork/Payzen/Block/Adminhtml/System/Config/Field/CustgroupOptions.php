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
 * Custom renderer for the customer group options field.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_CustgroupOptions
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_default = array();

    public function __construct()
    {
        $this->addColumn(
            'title',
            array(
                'label' => Mage::helper('payzen')->__('Customer group'),
                'style' => 'width: 260px;',
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_Label
            )
        );
        $this->addColumn(
            'amount_min',
            array(
                'label' => Mage::helper('payzen')->__('Minimum amount'),
                'style' => 'width: 210px;'
            )
        );
        $this->addColumn(
            'amount_max',
            array(
                'label' => Mage::helper('payzen')->__('Maximum amount'),
                'style' => 'width: 210px;'
            )
        );

        $this->_addAfter = false;

        parent::__construct();

        $this->setTemplate('payzen/field/array.phtml');
    }

    /**
     * Obtain existing data from form element.
     *
     * Each row will be instance of Varien_Object.
     *
     * @return array
     */
    public function getArrayRows()
    {
        /**
         * @var array[string][string] $groups
         */
        $groups = $this->_getAllCustomerGroups();

        $savedGroups = $this->getElement()->getValue();
        if (! is_array($savedGroups)) {
            $savedGroups = array();
        }

        if (! empty($savedGroups)) {
            foreach ($savedGroups as $id => $savedGroup) {
                if (key_exists($savedGroup['code'], $groups)) {
                    // Refresh group title.
                    $savedGroups[$id]['title'] = $groups[$savedGroup['code']];
                    if ($savedGroup['code'] === 'all') {
                        $savedGroups[$id]['color'] = '#e6e6e6';
                    }

                    unset($groups[$savedGroup['code']]);
                }
            }
        }

        // Add not saved yet groups.
        foreach ($groups as $code => $title) {
            $min = (($code === 'all') && isset($this->_default['amount_min'])) ? $this->_default['amount_min'] : '';
            $max = (($code === 'all') && isset($this->_default['amount_max'])) ? $this->_default['amount_max'] : '';

            $group = array(
                'code' => $code,
                'title' => $title,
                'amount_min' => $min,
                'amount_max' => $max
            );

            if ($code === 'all') {
                // Add all groups entry.
                $group['color'] = '#e6e6e6';
                $savedGroups = array_merge(array(uniqid('_all_') => $group), $savedGroups);
            } else {
                $savedGroups[uniqid('_' . $code . '_')] = $group;
            }
        }

        $this->getElement()->setValue($savedGroups);
        return parent::getArrayRows();
    }

    protected function _getAllCustomerGroups()
    {
        $options = array();
        $options['all'] = Mage::helper('payzen')->__('ALL GROUPS');

        foreach (Mage::getModel('customer/group')->getCollection() as $group) {
            $options[$group->getCustomerGroupId()] = $group->getCustomerGroupCode();
        }

        return $options;
    }
}
