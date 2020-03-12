<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_Oney3x4x_PaymentOptions extends Lyranetwork_Payzen_Model_Field_Array
{
    protected $_eventPrefix = 'payzen_field_oney3x4x_payment_options';

    public function _beforeSave()
    {
        $values = $this->getValue();
        Mage::helper('payzen')->log(print_r($values, true));

        if (! is_array($values) || count($values) <= 1) {
            if (strpos($this->_eventPrefix, '3x4x') !== false) { // Check if it's payment 3x 4x Oney.
                $field = Mage::helper('payzen')->__((string) $this->getFieldConfig()->label);
                $group = Mage::helper('payzen')->getConfigGroupTitle($this->getGroupId());
                $msg = Mage::helper('payzen')->__('The field &laquo; %s &raquo; is required for section &laquo; %s &raquo;.', $field, $group);

                // Throw exception.
                Mage::throwException($msg);
            } else {
                $this->setValue(array());
            }
        } else {
            $i = 0;
            foreach ($values as $value) {
                $i++;

                if (empty($value)) {
                    continue;
                }

                if (! preg_match('#^.{0,64}$#u', $value['label'])) {
                    $this->_throwError('Label', $i);
                }

                if (empty($value['code'])) {
                    $this->_throwError('Code', $i);
                }

                if (! empty($value['minimum']) && ! preg_match('#^\d+(\.\d+)?$#', $value['minimum'])) {
                    $this->_throwError('Min. amount', $i);
                }

                if (! empty($value['maximum']) && ! preg_match('#^\d+(\.\d+)?$#', $value['maximum'])) {
                    $this->_throwError('Max. amount', $i);
                }

                if (! preg_match('#^[1-9]\d*$#', $value['count'])) {
                    $this->_throwError('Count', $i);
                }

                if (! is_numeric($value['rate']) || $value['rate'] >= 100 || $value['rate'] < 0) {
                    $this->_throwError('Rate', $i);
                }
            }
        }

        return parent::_beforeSave();
    }
}
