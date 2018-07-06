<?php
/**
 * PayZen V2-Payment Module version 1.9.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */

class Lyra_Payzen_Model_Field_Multi_PaymentOptions extends Lyra_Payzen_Model_Field_Array
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
            foreach ($values as $key => $value) {
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
                    $this->_throwError('1st payment', $i);
                }

                $options[] = $value['count'];
            }

            Mage::helper('payzen')->updateOptionModelConfig($options);
        }

        return parent::_beforeSave();
    }
}
