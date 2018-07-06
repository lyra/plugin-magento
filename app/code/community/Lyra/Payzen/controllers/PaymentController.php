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

class Lyra_Payzen_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Redirect customer to the payment gateway.
     */
    public function formAction()
    {
        $this->_getDataHelper()->log('Start =================================================');
        $this->_getPaymentHelper()->doPaymentForm($this);
        $this->_getDataHelper()->log('End =================================================');
    }

    /**
     * Redirect customer to the payment gateway iframe.
     */
    public function iframeAction()
    {
        $this->_getDataHelper()->log('Start =================================================');
        $this->_getPaymentHelper()->doPaymentForm($this);
        $this->_getDataHelper()->log('End =================================================');
    }

    /**
     * Display iframe loader.
     */
    public function loaderAction()
    {
        $this->_getDataHelper()->log('Start =================================================');
        if ($this->getRequest()->getParam('mode', null) === 'cancel') {
            // load order
            $lastIncrementId = $this->getCheckout()->getLastRealOrderId();
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($lastIncrementId);

            if ($order->getId()) {
                $this->_getDataHelper()->log("Cancel order #{$order->getId()} to allow payment retry.");
                $order->registerCancellation($this->__('Payment canceled.'))->save();

                $this->_getDataHelper()->log("Clean session for #{$order->getId()} and restore last quote if any.");
                $this->getCheckout()->setLastBillingAgreementId(null)
                    ->setRedirectUrl(null)
                    ->setLastOrderId(null)
                    ->setLastRealOrderId(null)
                    ->setLastRecurringProfileIds(null)
                    ->setAdditionalMessages(null);

                $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                if ($quote->getId()) {
                    $quote->setIsActive(true)->setReservedOrderId(null)->save();
                    $this->getCheckout()->replaceQuote($quote);
                }
            }
        }

        $block = $this->getLayout()->createBlock('core/template')->setTemplate('payzen/iframe/loader.phtml');
        $this->getResponse()->setBody($block->toHtml());
        $this->_getDataHelper()->log('End =================================================');
    }

    /**
     * Action called after the client returns from payment gateway.
     */
    public function returnAction()
    {
        $this->_getDataHelper()->log('Start =================================================');
        $this->_getPaymentHelper()->doPaymentReturn($this);
        $this->_getDataHelper()->log('End =================================================');
    }

    /**
     * Action called by the payment gateway to notify payment result.
     * Note that admin payment also are notified by this action.
     */
    public function checkAction()
    {
        $this->_getDataHelper()->log('Start =================================================');
        $this->_getPaymentHelper()->doPaymentCheck($this);
        $this->_getDataHelper()->log('End =================================================');
    }

    /**
     * AJAX Action called when customer choose a new shipping address in 1-Click payment UI.
     */
    public function oneclickShippingAction()
    {
        if ($this->_ajaxExpire()) {
            return;
        }

        try {
            $oneClickQuote = $this->_updateOneclickQuote(true);
            $oneClickQuote->collectTotals()->save();
            $this->getPayzenSession()->unsetQuote();

            $layout = $this->getLayout();
            $update = $layout->getUpdate();
            $update->load('payzen_oneclick_shipping_method');
            $layout->generateXml();
            $layout->generateBlocks();

            $this->_returnJson(array('html' => $layout->getOutput()));
        } catch (Mage_Core_Exception $e) {
            $this->_returnJson(array('error' => true, 'message' => $e->getMessage()));
        }
    }

    /**
     * Action called when customer click PayZen 1-Click payment button.
     */
    public function oneclickPaymentAction()
    {
        $this->_getDataHelper()->log('Start =================================================');

        try {
            $this->_getDataHelper()->log(
                'Update PayZen 1-Click quote data (products, shipping address, shipping method).'
            );
            $oneClickQuote = $this->_updateOneclickQuote();

            // reload billing address
            $this->_getDataHelper()->log('Refresh PayZen 1-Click quote billing address.');
            $customerAddressId = $oneClickQuote->getBillingAddress()->getCustomerAddressId();
            $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
            $oneClickQuote->getBillingAddress()->importCustomerAddress($customerAddress)->setSaveInAddressBook(0);

            $this->_getDataHelper()->log('Add payment info to PayZen 1-Click quote.');
            if (! $oneClickQuote->isVirtual() && $oneClickQuote->getShippingAddress()) {
                $oneClickQuote->getShippingAddress()->setPaymentMethod('payzen_standard');
            } else {
                $oneClickQuote->getBillingAddress()->setPaymentMethod('payzen_standard');
            }

            $data = array('method' => 'payzen_standard', 'payzen_standard_use_identifier' => 1);
            $oneClickQuote->getPayment()->importData($data);

            $this->_getDataHelper()->log('Save PayZen 1-Click quote after total recollection.');
            $oneClickQuote->collectTotals()->setIsActive(true)->save();

            // reload PayZen 1-Click quote
            $this->getPayzenSession()->unsetQuote();
            $oneClickQuote = $this->getPayzenSession()->getQuote();

            if ($this->getCheckout()->getQuoteId()) {
                // save current quote ID to reload it farther
                $this->getPayzenSession()->setPayzenInitialQuoteId($this->getCheckout()->getQuoteId());
                $this->getCheckout()->getQuote()->setIsActive(false)->save();
            }

            // save PayZen 1-Click quote to checkout session
            $this->getPayzenSession()->setPayzenOneclickPayment(true)
                                     ->setPayzenOneclickBackUrl($this->_getRefererUrl());
            $this->getCheckout()->replaceQuote($oneClickQuote);

            $this->_getDataHelper()->log('Create order from PayZen 1-Click quote.');
            $service = Mage::getModel('sales/service_quote', $oneClickQuote);
            $order = $service->submit();

            $this->_getDataHelper()->log(
                'Set PayZen 1-Click payment information to session and redirect to payment page.'
            );
            $this->getCheckout()->setLastSuccessQuoteId($this->getCheckout()->getQuoteId())
                                ->setLastRealOrderId($order->getIncrementId());

            $redirectUrl = Mage::getUrl('payzen/payment/form', array('_secure' => true));
            $this->_redirectUrl($redirectUrl);
        } catch (Mage_Core_Exception $e) {
            $this->_getDataHelper()->log(
                'Error when trying to pay with PayZen 1-Click. ' . $e->getMessage(),
                Zend_Log::WARN
            );

            // disable PayZen 1-Click quote
            $oneClickQuote = $this->getPayzenSession()->getQuote();
            $oneClickQuote->setIsActive(false)->setReservedOrderId(null)->save();

            // restore initial checkout quote
            if ($this->getPayzenSession()->getPayzenInitialQuoteId()) {
                $quote = Mage::getModel('sales/quote')->load(
                    (int) $this->getPayzenSession()->getPayzenInitialQuoteId(true)
                );

                if ($quote->getId()) {
                    $quote->setIsActive(true)->save();
                    $this->getCheckout()->replaceQuote($quote);
                }
            }

            $this->getPayzenSession()->unsPayzenOneclickPayment()
                                     ->unsPayzenOneclickBackUrl();

            // use core/session instance to be able to show messages from all pages
            if ($this->getCoreSession()->getUseNotice(true)) {
                $this->getCoreSession()->addNotice($e->getMessage());
            } else {
                $this->getCoreSession()->addError($e->getMessage());
            }

            $this->_getDataHelper()->log('Redirecting to referer URL.');
            $this->_redirectUrl($this->_getRefererUrl());
        }

        $this->_getDataHelper()->log('End =================================================');
    }

    protected function _ajaxExpire()
    {
        $session = $this->getPayzenSession();

        if (! $this->getRequest()->isPost() || ! $session->getQuote() || $session->getQuote()->getHasError()) {
            $this->getResponse()
                    ->setHeader('HTTP/1.1', '403 Session Expired')
                    ->setHeader('Login-Required', 'true')
                    ->sendResponse();
            return true;
        }

        return false;
    }

    protected function _returnJson($result)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    protected function _updateOneclickQuote($ignoreNotices = false)
    {
        $oneClickQuote = $this->getPayzenSession()->getQuote();

        if ($productId = $this->getRequest()->getPost('product', false)) {
            $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load((int) $productId);

            if ($product->getId()) {
                // remove all 1-Click quote items to refresh it
                foreach ($oneClickQuote->getItemsCollection() as $item) {
                    $oneClickQuote->removeItem($item->getId());
                }

                $oneClickQuote->getShippingAddress()->removeAllShippingRates();
                $oneClickQuote->setCouponCode('');

                $request = new Varien_Object($this->getRequest()->getParams());
                if (! $request->hasQty()) {
                    $request->setQty(1);
                }

                try {
                    $result = $oneClickQuote->addProduct($product, $request);

                    if (is_string($result)) {
                        // error message
                        $this->getCoreSession()->setUseNotice(true);
                        Mage::throwException($result);
                    }
                } catch (Mage_Core_Exception $e) {
                    Mage::throwException($e->getMessage());
                } catch (Exception $e) {
                    Mage::throwException(
                        $this->__('Cannot pay requested product with &laquo;PayZen Buy now&raquo;.')
                    );
                }

                // related products
                $productIds = $this->getRequest()->getParam('related_product');
                if (! empty($productIds)) {
                    $productIds = explode(',', $productIds);

                    if (! empty($productIds)) {
                        $allAvailable = true;
                        $allAdded = true;

                        foreach ($productIds as $productId) {
                            $productId = (int) $productId;
                            if (! $productId) {
                                continue;
                            }

                            $product = Mage::getModel('catalog/product')
                                    ->setStoreId(Mage::app()->getStore()->getId())
                                    ->load($productId);
                            if ($product->getId() && $product->isVisibleInCatalog()) {
                                try {
                                    $oneClickQuote->addProduct($product);
                                } catch (Exception $e) {
                                    $allAdded = false;
                                }
                            } else {
                                $allAvailable = false;
                            }
                        }

                        if (! $ignoreNotices) {
                            if (! $allAvailable) {
                                $this->getCoreSession()->addError(
                                    $this->__('Some of the products you requested are unavailable.')
                                );
                            }

                            if (! $allAdded) {
                                $msg = 'Some of the products you requested are not available in the desired quantity.';
                                $this->getCoreSession()->addError($this->__($msg));
                            }
                        }
                    }
                }
            }
        }

        $addressId = $this->getRequest()->getPost('shipping_address', false);
        $customerAddress = Mage::getModel('customer/address')->load((int) $addressId);
        if (! $oneClickQuote->isVirtual() && $customerAddress->getId()) {
            if ($customerAddress->getCustomerId() != Mage::getSingleton('customer/session')->getCustomer()->getId()) {
                Mage::throwException($this->__('Customer Address is not valid.'));
            }

            $oneClickQuote->getShippingAddress()->importCustomerAddress($customerAddress);

            $method = $this->getRequest()->getPost('shipping_method', false);
            $oneClickQuote->getShippingAddress()->setShippingMethod($method);
        }

        $oneClickQuote->getShippingAddress()->setCollectShippingRates(true);
        $oneClickQuote->setTotalsCollectedFlag(false);

        return $oneClickQuote;
    }

    /**
     * Redirect to checkout initial page (when payment cannot be done).
     *
     * @param string $msg
     */
    public function redirectBack($msg)
    {
        // clear all messages from session
        $this->getCheckout()->getMessages(true);
        $this->getCoreSession()->getMessages(true);

        $this->_getDataHelper()->log($msg . ' Redirecting to cart page.');
        $this->_redirect('checkout/cart', array('_store' => Mage::app()->getStore()->getId()));
    }

    /**
     * Redirect to error page (when an unexpected error occurred).
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function redirectError($order)
    {
        // clear all messages in session
        $this->getCheckout()->getMessages(true);
        $this->getCoreSession()->getMessages(true);

        $this->_getDataHelper()->log("Redirecting to failure page for order #{$order->getId()}.");
        $this->_redirect('checkout/onepage/failure', array('_store' => $order->getStore()->getId()));
    }

    /**
     * Redirect to result page (according to payment status).
     *
     * @param Mage_Sales_Model_Order $order
     * @param boolean $success
     * @param boolean $checkUrlWarn
     */
    public function redirectResponse($order, $success, $checkUrlWarn = false)
    {
        // clear all messages in session
        $this->getCheckout()->getMessages(true);
        $this->getCoreSession()->getMessages(true);

        $storeId = $order->getStore()->getId();
        if ($this->_getDataHelper()->getCommonConfigData('ctx_mode', $storeId) == 'TEST') {
            if (Lyra_Payzen_Helper_Data::$pluginFeatures['prodfaq']) {
                // display going to production message
                $message = $this->__('<p><u>GOING INTO PRODUCTION</u></p>You want to know how to put your shop into production mode, please go to this URL : ');
                $message .= '<a href="https://secure.payzen.eu/html/faq/prod" target="_blank">https://secure.payzen.eu/html/faq/prod</a>';
                $this->getCoreSession()->addNotice($message);
            }

            if ($checkUrlWarn) {
                // order not validated by notification URL, in TEST mode, user is webmaster
                // so display a warning about notification URL not working

                if ($this->_getDataHelper()->isMaintenanceMode()) {
                    $message = $this->__('The shop is in maintenance mode.The automatic notification cannot work.');
                } else {
                    $message = $this->__('The automatic validation has not worked. Have you correctly set up the notification URL in your PayZen Back Office ?');
                    $message .= '<br /><br />';
                    $message .= $this->__('For understanding the problem, please read the documentation of the module :<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo;To read carefully before going further&raquo;<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo;Notification URL settings&raquo;');
                }

                $this->getCoreSession()->addError($message);
            }
        }

        // was this a PayZen 1-click payment ?
        $oneclick = $this->getPayzenSession()->getPayzenOneclickPayment(true);

        if ($success) {
            if ($oneclick) {
                $this->getPayzenSession()->unsetAll();
            }

            $this->getCheckout()->setLastQuoteId($order->getQuoteId())
                                ->setLastSuccessQuoteId($order->getQuoteId())
                                ->setLastOrderId($order->getId())
                                ->setLastRealOrderId($order->getIncrementId())
                                ->setLastOrderStatus($order->getStatus());

            $this->_getDataHelper()->log("Redirecting to success page for order #{$order->getId()}.");
            $this->_redirect('checkout/onepage/success', array('_store' => $storeId));
        } else {
            $this->_getDataHelper()->log("Unsetting order data in session for order #{$order->getId()}.");
            $this->getCheckout()->setLastBillingAgreementId(null)
                                ->setRedirectUrl(null)
                                ->setLastOrderId(null)
                                ->setLastRealOrderId(null)
                                ->setLastRecurringProfileIds(null)
                                ->setAdditionalMessages(null);

            $this->getCoreSession()->addWarning($this->__('Checkout and order have been canceled.'));

            if ($oneclick) {
                $oneClickQuote = $this->getPayzenSession()->getQuote();
                $oneClickQuote->setReservedOrderId(null)->save();
                $this->getPayzenSession()->unsetQuote();

                $this->getPayzenSession()->unsPayzenInitialQuoteId();

                // in case of 1-Click payment , redirect to referer URL
                $this->_getDataHelper()->log(
                    "Redirecting to referer URL (product view or cart page) for order #{$order->getId()}."
                );
                $this->_redirectUrl($this->getPayzenSession()->getPayzenOneclickBackUrl(true));
            } else {
                $this->_getDataHelper()->log("Restore cart for order #{$order->getId()} to allow re-order quicker.");

                $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                if ($quote->getId()) {
                    $quote->setIsActive(true)->setReservedOrderId(null)->save();
                    $this->getCheckout()->replaceQuote($quote);
                }

                $this->_getDataHelper()->log("Redirecting to cart page for order #{$order->getId()}.");
                $this->_redirect('checkout/cart', array('_store' => $storeId));
            }
        }
    }

    /**
     * Set redirect into response
     *
     * @param string $path
     * @param array $arguments
     * @return Mage_Core_Controller_Varien_Action
     */
    protected function _redirect($path, $arguments = array())
    {
        if ($this->getRequest()->getParam('iframe', false) /* if iframe payment */) {
            $block = $this->getLayout()->createBlock('payzen/iframe_response')
                                        ->setForwardUrl(Mage::getUrl($path, $arguments));

            $this->getResponse()->setBody($block->toHtml());
            return $this;
        } else {
            return parent::_redirect($path, $arguments);
        }
    }

    /**
     * Get PayZen 1-Click checkout session namespace.
     *
     * @return Lyra_Payzen_Model_Session
     */
    public function getPayzenSession()
    {
        return Mage::getSingleton('payzen/session');
    }

    /**
     * Get checkout session namespace.
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get main session namespace.
     *
     * @return Mage_Core_Model_Session
     */
    public function getCoreSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * Return PayZen data helper.
     *
     * @return Lyra_Payzen_Helper_Data
     */
    protected function _getDataHelper()
    {
        return Mage::helper('payzen');
    }

    /**
     * Return PayZen payment helper.
     *
     * @return Mage_Payzen_Helper_Payment
     */
    protected function _getPaymentHelper()
    {
        return Mage::helper('payzen/payment');
    }
}
