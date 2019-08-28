<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Block_Redirect extends Mage_Core_Block_Template
{

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Payment API instance.
     *
     * @return Lyranetwork_Payzen_Model_Payment_Abstract
     */
    protected function _getMethodInstance()
    {
        return $this->_getOrder()->getPayment()->getMethodInstance();
    }

    /**
     * Return order instance with loaded information by increment id
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if ($this->getOrder()) {
            $order = $this->getOrder();
        } elseif ($this->_getCheckout()->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($this->_getCheckout()->getLastRealOrderId());
        } else {
            $order = null;
        }

        return $order;
    }

    /**
     * Get Form data by using ops payment api
     *
     * @return array
     */
    public function getFormFields()
    {
        return $this->_getMethodInstance()->getFormFields($this->_getOrder());
    }

    /**
     * Getting gateway url
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->_getMethodInstance()->getPlatformUrl();
    }
}
