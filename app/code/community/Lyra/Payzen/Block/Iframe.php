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

class Lyra_Payzen_Block_Iframe extends Mage_Core_Block_Template
{
    /**
     * @var bool
     */
    protected $_shouldRender = false;

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
        if (!$this->_quote) {
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

    /**
     * Before rendering html, check if is block rendering needed.
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _beforeToHtml()
    {
        if ($this->_isIframeMode()) {
            $this->_shouldRender = true;
        }

        return parent::_beforeToHtml();
    }

    protected function _isIframeMode()
    {
        $check = ($this->_getMethodInstance() instanceof  Lyra_Payzen_Model_Payment_Standard)
            && $this->_getMethodInstance()->isIframeMode();

        return $check;
    }

    /**
     * Render the block if needed.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_shouldRender) {
            return '';
        }

        return parent::_toHtml();
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
