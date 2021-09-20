<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Payment_Oney3x4x extends Lyranetwork_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_oney3x4x';
    protected $_formBlockType = 'payzen/oney3x4x';

    protected $_canUseInternal = false;

    protected $_currencies = array('EUR');
    protected $needsCartData = true;

    protected  function _setExtraFields($order)
    {
        // Override with Oney 3x/4x payment cards.
        $this->_payzenRequest->set('payment_cards', 'ONEY_3X_4X');

        // Set choosen option if any.
        $info = $this->getInfoInstance();
        $option = @unserialize($info->getAdditionalData());
        $this->_payzenRequest->set('payment_option_code', $option['code']);
    }

    protected function _proposeOney()
    {
        return true;
    }

    /**
     * Assign data to info model instance.
     *
     * @param  mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        $info = $this->getInfoInstance();

        $option = $this->_getOption($data->getPayzenOney3x4xOption());
        if (! $option) {
            Mage::throwException($this->_getHelper()->__('Please select a payment option.'));
        }

        // Init all payment data.
        $info->setAdditionalData(serialize($option))
            ->setCcType(null)
            ->setCcLast4(null)
            ->setCcNumber(null)
            ->setCcCid(null)
            ->setCcExpMonth(null)
            ->setCcExpYear(null);

        return $this;
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

        $options = array();
        foreach ($configOptions as $code => $value) {
            if (empty($value)) {
                continue;
            }

            if ((! $value['minimum'] || ($amount > $value['minimum'])) && (! $value['maximum'] || ($amount < $value['maximum']))) {
                // Option will be available.
                $c = is_numeric($value['count']) ? $value['count'] : 1;
                $r = is_numeric($value['rate']) ? $value['rate'] : 0;

                // Get final option description.
                $search = array('%c', '%r');
                $replace = array($c, $r . ' %');
                $value['label'] = str_replace($search, $replace, $value['label']); // Label to display on payment page.

                $options[$code] = $value;
            }
        }

        return $options;
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
     * To check billing and shipping countries are allowed for Oney payment method.
     *
     * @return bool
     */
    public function canUseForCountry($country)
    {
        $availableCountries = Mage::getModel('payzen/source_oney3x4x_availableCountries')->getCountryCodes();

        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));
        }

        return in_array($country, $availableCountries);
    }

    protected function _canUseForOptions($quote = null)
    {
        $amount = $quote ? $quote->getBaseGrandTotal() : null;
        if ($amount) {
            $options = $this->getPaymentOptions($amount);
            return ! empty($options);
        }

        return false;
    }

    /**
     * Check whether payment method can be used
     *
     * @param  Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $checkResult = parent::isAvailable($quote);

        if (! $checkResult || ! $quote) {
            return $checkResult;
        }

        if (! $this->_canUseForOptions($quote)) {
            return false;
        }

        // Check shipping country, billing country is checked in parent::isAvailable method.
        if (! $this->canUseForCountry($quote->getShippingAddress()->getCountry())) {
            return false;
        }

        if ($quote->getCustomerId() && ! preg_match(Lyranetwork_Payzen_Helper_Util::CUST_ID_REGEX, $quote->getCustomerId())) {
            // Customer ID doesn't match Oney rules.

            $msg = 'Customer ID "%s" does not match the gateway specifications. The regular expression for this field is %s. %s payment means cannot be used.';
            $this->_getHelper()->log(sprintf($msg, $quote->getCustomerId(), Lyranetwork_Payzen_Helper_Util::CUST_ID_REGEX, $this->_code), Zend_Log::WARN);
            return false;
        }

        if (! $quote->getReservedOrderId()) {
            $quote->reserveOrderId(); // Guess order ID.
        }

        if (! preg_match(Lyranetwork_Payzen_Helper_Util::ORDER_ID_REGEX, $quote->getReservedOrderId())) {
            // Order ID doesn't match Oney rules.

            $msg = 'The order ID "%s" does not match gateway specifications. The regular expression for this field is %s. %s payment means cannot be used.';
            $this->_getHelper()->log(sprintf($msg, $quote->getReservedOrderId(), Lyranetwork_Payzen_Helper_Util::ORDER_ID_REGEX, $this->_code), Zend_Log::WARN);
            return false;
        }

        foreach ($quote->getAllItems() as $item) {
            // Check to avoid sending the whole hierarchy of a configurable product.
            if ($item->getParentItem()) {
                continue;
            }

            if (! preg_match(Lyranetwork_Payzen_Helper_Util::PRODUCT_REF_REGEX, $item->getProductId())) {
                // Product ID doesn't match Oney rules.

                $msg = 'Product reference "%s" does not match gateway specifications. The regular expression for this field is %s. %s payment means cannot be used.';
                $this->_getHelper()->log(sprintf($msg, $item->getProductId(), Lyranetwork_Payzen_Helper_Util::PRODUCT_REF_REGEX, $this->_code), Zend_Log::WARN);
                return false;
            }
        }

        if (! $quote->isVirtual() && $quote->getShippingAddress()->getShippingMethod()) {
            $shippingMethod = Mage::helper('payzen/util')->toPayzenCarrier($quote->getShippingAddress()->getShippingMethod());
            if (! $shippingMethod) {
                // Selected shipping method is not mapped in configuration panel.

                $this->_getHelper()->log('Shipping method "' . $quote->getShippingAddress()->getShippingMethod() . '" is not correctly mapped in module configuration panel. Module is not displayed.', Zend_Log::WARN);
                return false;
            }
        }

        return true;
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
            $shippingAddress = $info->getQuote()->isVirtual() ? null : $info->getQuote()->getShippingAddress();
        }

        Mage::helper('payzen/util')->checkAddressValidity($billingAddress, 'oney3x4x');
        Mage::helper('payzen/util')->checkAddressValidity($shippingAddress, 'oney3x4x');

        return $this;
    }
}
