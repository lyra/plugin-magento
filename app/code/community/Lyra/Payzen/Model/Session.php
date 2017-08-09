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

class Lyra_Payzen_Model_Session extends Mage_Checkout_Model_Session
{

    public function __construct()
    {
        $this->init('payzen');
    }

    /**
     * Get PayZen 1-Click quote instance by current session
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

            if (!$this->getQuoteId()) {
                // logged in customer
                $customer = Mage::getSingleton('customer/session')->getCustomer();

                // set default customer shipping and billing address
                $quote->assignCustomer($customer);

                // load the last passed order if any
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
                    // last used shipping address
                    if ($order->getShippingAddress()) {
                        $address = Mage::getModel('customer/address')->load($order->getShippingAddress()->getCustomerAddressId());
                        if ($address && $address->getId()) {
                            $quote->getShippingAddress()->importCustomerAddress($address);
                        }
                    }

                    // last used shipping method
                    if ($order->getShippingMethod()) {
                        $quote->getShippingAddress()->setShippingMethod($order->getShippingMethod());
                    }
                }
            }

            // do not activate quote until the PayZen 1-Click button is clicked
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
