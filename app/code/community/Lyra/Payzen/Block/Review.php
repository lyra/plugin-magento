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

class Lyra_Payzen_Block_Review extends Mage_Core_Block_Template
{
    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    /**
     * Get checkout session namespace.
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get quote object.
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        if (! $this->_quote) {
            $this->_quote = $this->_getCheckout()->getQuote();
        }

        return $this->_quote;
    }

    /**
     * PayZen payment API instance
     *
     * @return Lyra_Payzen_Model_Payment_Abstract
     */
    protected function _getMethodInstance()
    {
        if ($this->_getQuote() && $this->_getQuote()->getPayment()) {
            return $this->_getQuote()->getPayment()->getMethodInstance();
        }

        return null;
    }

    public function chooseTemplate()
    {
        if ($this->_isIframeMode()) {
            $this->setTemplate($this->getIframeTemplate());
        } else {
            $this->setTemplate(null);
        }
    }

    protected function _isIframeMode()
    {
        $check = ($this->_getMethodInstance() instanceof  Lyra_Payzen_Model_Payment_Standard)
            && $this->_getMethodInstance()->isIframeMode();

        return $check;
    }

    /**
     * Return payzen data helper.
     *
     * @return Lyra_Payzen_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('payzen');
    }
}
