<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_Oney_CustgroupOptions extends Lyranetwork_Payzen_Model_Field_CustgroupOptions
{
    protected function _beforeSave()
    {
        $values = $this->getValue();

        $data = $this->getGroups('payzen_oney'); // Get data of FacilyPay Oney config group.
        if ($data['fields']['oney_active']['value']) { // FacilyPay Oney is activated.
            foreach ($values as $value) {
                if (empty($value) || ($value['code'] !== 'all')) {
                    continue;
                }

                if (empty($value['amount_min'])) {
                    $field = 'Minimum amount';
                } elseif (empty($value['amount_max'])) {
                    $field = 'Maximum amount';
                }

                if (isset($field)) {
                    $field = Mage::helper('payzen')->__($field); // Translate field name.
                    $group = Mage::helper('payzen')->getConfigGroupTitle($this->getGroupId());
                    $msg = Mage::helper('payzen')->__('Please enter a value for &laquo; ALL GROUPS - %s &raquo; in &laquo; %s &raquo; section as agreed with your bank.', $field, $group);

                    // Throw exception.
                    Mage::throwException($msg);
                }
            }
        }

        return parent::_beforeSave();
    }
}
