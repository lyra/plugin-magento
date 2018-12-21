<?php
/**
 * PayZen V2-Payment Module version 1.9.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lyra_Payzen_Model_Payment_Fullcb extends Lyra_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_fullcb';
    protected $_formBlockType = 'payzen/fullcb';

    protected $_canUseInternal = false;

    protected $_currencies = array('EUR');

    protected  function _setExtraFields($order)
    {
        // override with FullCb specific params
        $this->_payzenRequest->set('cust_status', 'PRIVATE');
        $this->_payzenRequest->set('validation_mode', '0');
        $this->_payzenRequest->set('capture_delay', '0');

        // override with selected Full CB payment card
        $info = $this->getInfoInstance();

        // set choosen option if any
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

        // init all payment data
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
                // option will be available
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
