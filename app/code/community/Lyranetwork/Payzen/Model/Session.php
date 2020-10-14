<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Session extends Mage_Checkout_Model_Session
{

    public function __construct()
    {
        $this->init('payzen');
    }

    /**
     * Get gateway 1-Click quote instance by current session
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if ($this->_quote === null) {
            $quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore()->getId());

            if ($this->getQuoteId()) {
                $quote->load($this->getQuoteId());

                if ($quote->getId()) {
                    /**
                     * If current currency code of quote is not equal current currency code of store,
                     * need recalculate totals of quote. It is possible if customer use currency switcher or
                     * store switcher.
                     */
                    if ($quote->getQuoteCurrencyCode() != Mage::app()->getStore()->getCurrentCurrencyCode()) {
                        $quote->setStore(Mage::app()->getStore());
                        $quote->collectTotals()->save();
                        /*
                         * We mast to create new quote object, because collectTotals()
                         * can to create links with other objects.
                        */
                        $quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore()->getId());
                        $quote->load($this->getQuoteId());
                    }
                } else {
                    $this->setQuoteId(null);
                }
            }

            if (! $this->getQuoteId()) {
                // Logged in customer.
                $customer = Mage::getSingleton('customer/session')->getCustomer();

                // Set default customer shipping and billing address.
                $quote->assignCustomer($customer);

                // Load the last passed order if any.
                $orders = Mage::getModel('sales/order')->getCollection();
                if (version_compare(Mage::getVersion(), '1.4.1.1', '<')) {
                    $orders->addAttributeToSelect('shipping_method');
                }

                $orders->addFilter('customer_id', $customer->getId())
                    ->addFilter('is_virtual', 0)
                    ->setOrder('created_at', Varien_Data_Collection_Db::SORT_ORDER_DESC)
                    ->setPageSize(1)
                    ->setCurPage(1);
                $order = $orders->getFirstItem();

                if ($order && $order->getId()) {
                    // Last used shipping address.
                    if ($order->getShippingAddress()) {
                        $address = Mage::getModel('customer/address')->load($order->getShippingAddress()->getCustomerAddressId());
                        if ($address && $address->getId()) {
                            $quote->getShippingAddress()->importCustomerAddress($address);
                        }
                    }

                    // Last used shipping method.
                    if ($order->getShippingMethod()) {
                        $quote->getShippingAddress()->setShippingMethod($order->getShippingMethod());
                    }
                }
            }

            // Do not activate quote until the 1-Click button is clicked.
            $quote->setIsActive(false);

            $quote->setStore(Mage::app()->getStore());
            $this->_quote = $quote;
        }

        if ($remoteAddr = Mage::helper('core/http')->getRemoteAddr()) {
            $this->_quote->setRemoteIp($remoteAddr);
            $xForwardIp = Mage::app()->getRequest()->getServer('HTTP_X_FORWARDED_FOR');
            $this->_quote->setXForwardedFor($xForwardIp);
        }

        return $this->_quote;
    }

    public function unsetQuote()
    {
        $this->_quote = null;
    }
}
