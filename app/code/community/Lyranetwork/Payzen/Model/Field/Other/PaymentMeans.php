<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_Other_PaymentMeans extends Lyranetwork_Payzen_Model_Field_Array
{
    protected $_eventPrefix = 'payzen_field_other_payment_means';

    protected function _beforeSave()
    {
        $values = $this->getValue();

        $savedOptions = array();
        foreach ($values as $value) {
            if (isset($value['means'])){
                $savedOptions[] = $value['means'];
            }
        }

        $occurences = array_count_values($savedOptions);

        if (! is_array($values) || empty($values)) {
            $this->setValue(array());
        } else {
            $i = 0;
            $means = array();
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

                if (! empty($value['minimum']) && ! preg_match('#^\d+(\.\d+)?$#', $value['capture_delay'])) {
                    $this->_throwError('Capture delay', $i);
                }

                if ($occurences[$value['means']] > 1) {
                    // Do not save several options with the same means of payment.
                    $this->_throwError('Payment means', $i, 'You cannot enable several options with the same means of payment.');
                }

                $means[$value['means']] = $value['label'];
            }

            Mage::helper('payzen')->updateMeanModelConfig($means);
        }

        return parent::_beforeSave();
    }
}
