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

class Lyra_Payzen_Model_Payment_Oney extends Lyra_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_oney';
    protected $_formBlockType = 'payzen/oney';

    protected $_canUseInternal = false;

    protected $_currencies = array('EUR');

    protected  function _setExtraFields($order)
    {
        $testMode = $this->_payzenRequest->get('ctx_mode') == 'TEST';

        // override with FacilyPay Oney payment cards
        $this->_payzenRequest->set('payment_cards', $testMode ? 'ONEY_SANDBOX' : 'ONEY');

        // set choosen option if any
        $info = $this->getInfoInstance();
        if ($info->getAdditionalData() && ($option = @unserialize($info->getAdditionalData()))) {
            $this->_payzenRequest->set('payment_option_code', $option['code']);
        }
    }

    protected function _proposeOney()
    {
        return true;
    }

    /**
     * Assign data to info model instance
     *
     * @param mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        $info = $this->getInfoInstance();

        $option = $this->_getOption($data->getPayzenOneyOption());

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
     * Get available payment options for the current cart amount.
     * @param double $amount a given amount
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
                // option will be available
                $c = is_numeric($value['count']) ? $value['count'] : 1;
                $r = is_numeric($value['rate']) ? $value['rate'] : 0;

                // get final option description
                $search = array('%c', '%r');
                $replace = array($c, $r . ' %');
                $value['label'] = str_replace($search, $replace, $value['label']); // label to display on payment page

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
     * To check billing and shipping countries are allowed for FacilyPay Oney payment method.
     *
     * @return bool
     */
    public function canUseForCountry($country)
    {
        $availableCountries = Mage::getModel('payzen/source_oney_availableCountries')->getCountryCodes();

        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));
        }

        return in_array($country, $availableCountries);
    }

    /**
     * Check whether payment method can be used
     *
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $checkResult = parent::isAvailable($quote);

        if (! $checkResult || ! $quote) {
            return $checkResult;
        }

        // check shipping country, billing country is checked in parent::isAvailable method.
        if (! $this->canUseForCountry($quote->getShippingAddress()->getCountry())) {
            return false;
        }

        if ($quote->getCustomerId() && ! preg_match(Lyra_Payzen_Helper_Util::CUST_ID_REGEX, $quote->getCustomerId())) {
            // customer id doesn't match FacilyPay Oney rules

            $msg = 'Customer ID "%s" does not match PayZen specifications. The regular expression for this field is %s. FacilyPay Oney means of payment cannot be used.';
            $this->_getHelper()->log(sprintf($msg, $quote->getCustomerId(), Lyra_Payzen_Helper_Util::CUST_ID_REGEX), Zend_Log::WARN);
            return false;
        }

        if (! $quote->getReservedOrderId()) {
            $quote->reserveOrderId(); // guess order id
        }

        if (! preg_match(Lyra_Payzen_Helper_Util::ORDER_ID_REGEX, $quote->getReservedOrderId())) {
            // order id doesn't match FacilyPay Oney rules

            $msg = 'The order ID "%s" does not match PayZen specifications. The regular expression for this field is %s. FacilyPay Oney means of payment cannot be used.';
            $this->_getHelper()->log(sprintf($msg, $quote->getReservedOrderId(), Lyra_Payzen_Helper_Util::ORDER_ID_REGEX), Zend_Log::WARN);
            return false;
        }

        foreach ($quote->getAllItems() as $item) {
            // check to avoid sending the whole hierarchy of a configurable product
            if ($item->getParentItem()) {
                continue;
            }

            if (! preg_match(Lyra_Payzen_Helper_Util::PRODUCT_REF_REGEX, $item->getProductId())) {
                // product id doesn't match FacilyPay Oney rules

                $msg = 'Product reference "%s" does not match PayZen specifications. The regular expression for this field is %s. FacilyPay Oney means of payment cannot be used.';
                $this->_getHelper()->log(sprintf($msg, $item->getProductId(), Lyra_Payzen_Helper_Util::PRODUCT_REF_REGEX), Zend_Log::WARN);
                return false;
            }
        }

        if (! $quote->isVirtual() && $quote->getShippingAddress()->getShippingMethod()) {
            $shippingMethod = Mage::helper('payzen/util')->toPayzenCarrier($quote->getShippingAddress()->getShippingMethod());
            if (! $shippingMethod) {
                // selected shipping method is not mapped in configuration panel

                $this->_getHelper()->log('Shipping method "' . $quote->getShippingAddress()->getShippingMethod() . '" is not correctly mapped in module configuration panel. Module is not displayed.', Zend_Log::WARN);
                return false;
            }
        }

        return true;
    }
}
