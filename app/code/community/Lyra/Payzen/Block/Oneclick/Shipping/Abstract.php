<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

abstract class Lyra_Payzen_Block_Oneclick_Shipping_Abstract extends Mage_Core_Block_Template
{
    protected $_checkout = null;
    protected $_quote = null;
    protected $_address = null;

    /**
     * Get 1-Click checkout session.
     *
     * @return Lyra_Payzen_Model_Session
     */
    public function getCheckout()
    {
        if (null === $this->_checkout) {
            $this->_checkout = Mage::getSingleton('payzen/session');
        }

        return $this->_checkout;
    }

    /**
     * Get 1-Click active quote.
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if (null === $this->_quote) {
            $this->_quote = $this->getCheckout()->getQuote();
        }

        return $this->_quote;
    }

    /**
     * Get address model.
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getAddress()
    {
        if (null === $this->_address) {
            $this->_address = $this->getQuote()->getShippingAddress();
        }

        return $this->_address;
    }

    protected function _afterToHtml($html)
    {
        $this->_quote = null;
        $this->_address = null;

        return parent::_afterToHtml($html);
    }
}
