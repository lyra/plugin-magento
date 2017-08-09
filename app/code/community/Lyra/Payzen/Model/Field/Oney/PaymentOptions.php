<?php
/**
 * PayZen V2-Payment Module version 1.7.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lyra_Payzen_Model_Field_Oney_PaymentOptions extends Lyra_Payzen_Model_Field_Array
{
    protected $_eventPrefix = 'payzen_field_oney_payment_options';

    public function _beforeSave()
    {
        $values = $this->getValue();

        if (!is_array($values) || empty($values)) {
            $this->setValue(array());
        } else {
            $i = 0;
            foreach ($values as $key => $value) {
                $i++;

                if (empty($value)) {
                    continue;
                }

                if (!preg_match('#^.{0,64}$#u', $value['label'])) {
                    $this->_throwError('Label', $i);
                }
                if (empty($value['code'])) {
                    $this->_throwError('Code', $i);
                }
                if (!empty($value['minimum']) && !preg_match('#^\d+(\.\d+)?$#', $value['minimum'])) {
                    $this->_throwError('Min. amount', $i);
                }
                if (!empty($value['maximum']) && !preg_match('#^\d+(\.\d+)?$#', $value['maximum'])) {
                    $this->_throwError('Max. amount', $i);
                }
                if (!preg_match('#^[1-9]\d*$#', $value['count'])) {
                    $this->_throwError('Count', $i);
                }
                if (!is_numeric($value['rate']) || $value['rate'] >= 100 || $value['rate'] < 0) {
                    $this->_throwError('Rate', $i);
                }
            }
        }

        return parent::_beforeSave();
    }
}
