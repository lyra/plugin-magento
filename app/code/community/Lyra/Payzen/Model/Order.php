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

class Lyra_Payzen_Model_Order extends Mage_Sales_Model_Order
{
    /**
     * Check order state before saving.
     */
    protected function _checkState()
    {
        if ($this->_isPayzenPayment() && $this->isPaymentReview()) {
            return $this;
        } else {
            return parent::_checkState();
        }
    }

    protected function _isPayzenPayment()
    {
        if ($this->getPayment()) {
            return stripos($this->getPayment()->getMethod(), 'payzen_') === 0;
        }

        return false;
    }

    /**
     * For compatibility with Magento 1.4 versions.
     * Check whether the payment is in payment review state
     * In this state order cannot be normally processed. Possible actions can be:
     * - accept or deny payment
     * - fetch transaction information
     *
     * @return boolean
     */
    public function isPaymentReview()
    {
        if (method_exists('Mage_Sales_Model_Order', 'isPaymentReview')) {
            return parent::isPaymentReview();
        } else {
            return $this->getState() === 'payment_review';
        }
    }

    /**
     * Retrieve label of order status. Allow to
     *
     * @return string
     */
    public function getStatusLabel()
    {
        if (! Mage::app()->getStore()->isAdmin() && $this->getStatus() == 'fraud') {
            return $this->getConfig()->getStatusLabel('processing');
        }

        return parent::getStatusLabel();
    }
}
