<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Payment_Fullcb extends Lyranetwork_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_fullcb';
    protected $_formBlockType = 'payzen/fullcb';

    protected $_canUseInternal = false;

    protected $_currencies = array('EUR');

    protected  function _setExtraFields($order)
    {
        // Override with Full CB specific params.
        $this->_payzenRequest->set('cust_status', 'PRIVATE');
        $this->_payzenRequest->set('validation_mode', '0');
        $this->_payzenRequest->set('capture_delay', '0');

        // Override with selected Full CB payment card.
        $info = $this->getInfoInstance();

        // Set choosen option if any.
        if ($info->getAdditionalData() && ($option = @unserialize($info->getAdditionalData()))) {
            $this->_payzenRequest->set('payment_cards', $option['code']);
        } else {
            $this->_payzenRequest->set('payment_cards', 'FULLCB3X;FULLCB4X');
        }
    }

    /**
     * Assign data to info model instance
     *
     * @param  mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        $info = $this->getInfoInstance();

        $option = $this->_getOption($data->getPayzenFullcbOption());

        // Init all payment data.
        $info->setAdditionalData($option ? serialize($option) : null)
            ->setCcType(null)
            ->setCcLast4(null)
            ->setCcNumber(null)
            ->setCcCid(null)
            ->setCcExpMonth(null)
            ->setCcExpYear(null);

        return $this;
    }

    /**
     * Return true if the method can be used at this time
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (! parent::isAvailable($quote)) {
            return false;
        }

        $amount = $quote ? $quote->getBaseGrandTotal() : null;
        if ($amount) {
            if ($this->getConfigData('enable_payment_options') == 1) {
                $options = $this->getPaymentOptions($amount);
                return ! empty($options);
            }
        }

        return true;
    }

    /**
     * Get available payment options for the current cart amount.
     *
     * @param  double $amount a given amount
     * @return array[string][array] an array "$code => $option" of available options
     */
    public function getPaymentOptions($amount)
    {
        $configOptions = unserialize($this->getConfigData('payment_options'));
        if (! is_array($configOptions) || empty($configOptions)) {
            return false;
        }

        $availOptions = array();
        foreach ($configOptions as $code => $value) {
            if (empty($value)) {
                continue;
            }

            if ((! $amount || ! $value['amount_min'] || $amount > $value['amount_min'])
                && (! $amount || ! $value['amount_max'] || $amount < $value['amount_max'])
            ) {
                // Option will be available.
                $availOptions[$code] = $value;
            }
        }

        return $availOptions;
    }

    protected function _getOption($code)
    {
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $amount = $info->getOrder()->getBaseGrandTotal();
        } else {
            $amount = $info->getQuote()->getBaseGrandTotal();
        }

        $options = $this->getPaymentOptions($amount);
        if ($code && $options[$code]) {
            return $options[$code];
        } else {
            return false;
        }
    }

    /**
     * Validate payment method information object
     *
     * @param  Mage_Payment_Model_Info $info
     * @return Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $billingAddress = $info->getOrder()->getBillingAddress();
            $shippingAddress = $info->getOrder()->getIsVirtual() ? null : $info->getOrder()->getShippingAddress();
        } else {
            $billingAddress = $info->getQuote()->getBillingAddress();
            $shippingAddress =  $info->getQuote()->isVirtual() ? null : $info->getQuote()->getShippingAddress();
        }

        Mage::helper('payzen/util')->checkAddressValidity($billingAddress, 'fullcb');
        Mage::helper('payzen/util')->checkAddressValidity($shippingAddress, 'fullcb');

        return $this;
    }
}
