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

    private function _isEmpty($values)
    {
        if (! is_array($values) || empty($values)) {
            return true;
        }

        if ((count($values) === 1) && isset($values['__empty'])) {
            return true;
        }

        return false;
    }

    public function _beforeSave()
    {
        $values = $this->getValue();

        $data = $this->getGroups('payzen_oney3x4x'); // Get data of method config group.
        if ($data['fields']['oney3x4x_active']['value']) { // Method is activated.
            if ($this->_isEmpty($values)) {
                $field = Mage::helper('payzen')->__((string) $this->getFieldConfig()->label);
                $group = Mage::helper('payzen')->getConfigGroupTitle($this->getGroupId());
                $msg = Mage::helper('payzen')->__('The field &laquo; %s &raquo; is required for section &laquo; %s &raquo;.', $field, $group);

                // Throw exception.
                Mage::throwException($msg);
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

                    $this->checkAmount($value['minimum'], 'Min. amount', $i);
                    $this->checkAmount($value['maximum'], 'Max. amount', $i);
                    $this->checkMandatoryDecimal($value['count'], 'Count', $i);
                    $this->checkRate($value['rate'], 'Rate', $i);
                }
            }
        }

        return parent::_beforeSave();
    }
}
