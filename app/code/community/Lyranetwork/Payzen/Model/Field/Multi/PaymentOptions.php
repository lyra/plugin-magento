<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_Multi_PaymentOptions extends Lyranetwork_Payzen_Model_Field_Array
{
    protected $_eventPrefix = 'payzen_field_multi_payment_options';

    protected function _beforeSave()
    {
        $values = $this->getValue();

        if (! is_array($values) || empty($values)) {
            $this->setValue(array());
        } else {
            $i = 0;
            $options = array();
            foreach ($values as $value) {
                $i++;

                if (empty($value)) {
                    continue;
                }

                if (empty($value['label'])) {
                    $this->_throwError('Label', $i);
                }

                if (! empty($value['minimum']) && ! preg_match('#^\d+(\.\d+)?$#', $value['minimum'])) {
                    $this->_throwError('Min. amount', $i);
                }

                if (! empty($value['maximum']) && ! preg_match('#^\d+(\.\d+)?$#', $value['maximum'])) {
                    $this->_throwError('Max. amount', $i);
                }

                if (! empty($value['contract']) && ! preg_match('#^[^;=]+$#', $value['contract'])) {
                    $this->_throwError('Contract', $i);
                }

                if (! preg_match('#^[1-9]\d*$#', $value['count'])) {
                    $this->_throwError('Count', $i);
                }

                if (! preg_match('#^[1-9]\d*$#', $value['period'])) {
                    $this->_throwError('Period', $i);
                }

                if (! empty($value['first']) && (! is_numeric($value['first']) || $value['first'] >= 100)) {
                    $this->_throwError('1st installment', $i);
                }

                $options[] = $value['count'];
            }

            Mage::helper('payzen')->updateOptionModelConfig($options);
        }

        return parent::_beforeSave();
    }
}
