<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_PaymentCards extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        if (! is_array($this->getValue()) || in_array('', $this->getValue())) {
            $this->setValue(array());
        }

        if (strlen(implode(';', $this->getValue())) > 127) {
            $field = Mage::helper('payzen')->__((string) $this->getFieldConfig()->label);
            $group = Mage::helper('payzen')->getConfigGroupTitle($this->getGroupId());

            $msg = sprintf(Mage::helper('payzen')->__('Invalid value for field &laquo; %s &raquo; in section &laquo; %s &raquo;.'), $field, $group);
            $msg .= ' ' . Mage::helper('payzen')->__('Too many card types are selected.');
            Mage::throwException($msg);
        }

        return parent::save();
    }
}
