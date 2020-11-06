<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_PaymentController extends Mage_Core_Controller_Front_Action
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
            // Load order.
            $lastIncrementId = $this->getCheckout()->getLastRealOrderId();
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($lastIncrementId);

            if ($order->getId()) {
                $this->_getDataHelper()->log("Cancel order #{$order->getIncrementId()} to allow payment retry.");
                $order->registerCancellation($this->__('Payment canceled.'))->save();

                $this->_getDataHelper()->log("Clean session for #{$order->getIncrementId()} and restore last quote if any.");
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
     * Action called to check order agreements for a payment by REST API.
     */
    public function restCheckOrderAction()
    {
        if ($this->_ajaxExpire()) {
            return;
        }

        $result = array();

        $requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
        if (! $this->getRequest()->getPost('skip_agreement', false) && $requiredAgreements) {
            $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
            $diff = array_diff($requiredAgreements, $postedAgreements);
            if ($diff) {
                $result['success'] = false;
                $result['error_messages'] = Mage::helper('checkout')->__('Please agree to all the terms and conditions before placing the order.');
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }
        }

        $isIdentifier = $this->getRequest()->getPost('is_identifier');
        if ($isIdentifier === null) {
            $isIdentifier = Mage::getSingleton('checkout/session')->getIdentifierPayment();
        }

        $model = Mage::getModel('payzen/payment_standard');
        $token = $model->getFormToken($isIdentifier);

        if ($token) {
            $result['formToken'] = $token;
        }

        $quote = $this->getCheckout()->getQuote();
        $quote->collectTotals()->save();

        $result['success'] = true;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Action called to clear quote for a payment by REST API.
     */
    public function restClearCartAction()
    {
        if ($this->_ajaxExpire()) {
            return;
        }

        $result = array();

        // Clear quote data.
        $this->getCheckout()->setQuoteId(null);
        $this->getCheckout()->setLastSuccessQuoteId(null);

        $quote = $this->getCheckout()->getQuote();

        if ($quote->getId()) {
            $quote->getPayment()->unsAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::TOKEN_DATA);
            $quote->getPayment()->unsAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::TOKEN);
            $quote->getPayment()->unsAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::TOKEN_DATA . '_identifier');
            $quote->getPayment()->unsAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::TOKEN . '_identifier');

            // Disable quote.
            $quote->setIsActive(false)->save();
            $this->_getHelper()->log("Cleared quote, reserved order ID: #{$quote->getReservedOrderId()}.");
        }

        $result['success'] = true;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Action called after the client returns from payment gateway.
     */
    public function restReturnAction()
    {
        $this->_getDataHelper()->log('Start =================================================');
        $this->_getPaymentHelper()->doPaymentRestReturn($this);
        $this->_getDataHelper()->log('End =================================================');
    }

    /**
     * Action called by the payment gateway to notify payment result.
     * Note that admin payment also are notified by this action.
     */
    public function restCheckAction()
    {
        $this->_getDataHelper()->log('Start =================================================');
        $this->_getPaymentHelper()->doPaymentRestCheck($this);
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
     * Action called when customer click 1-Click payment button.
     */
    public function oneclickPaymentAction()
    {
        $this->_getDataHelper()->log('Start =================================================');

        try {
            $this->_getDataHelper()->log(
                'Update 1-Click quote data (products, shipping address, shipping method).'
            );
            $oneClickQuote = $this->_updateOneclickQuote();

            // Reload billing address.
            $this->_getDataHelper()->log('Refresh 1-Click quote billing address.');
            $customerAddressId = $oneClickQuote->getBillingAddress()->getCustomerAddressId();
            $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
            $oneClickQuote->getBillingAddress()->importCustomerAddress($customerAddress)->setSaveInAddressBook(0);

            $this->_getDataHelper()->log('Add payment info to 1-Click quote.');
            if (! $oneClickQuote->isVirtual() && $oneClickQuote->getShippingAddress()) {
                $oneClickQuote->getShippingAddress()->setPaymentMethod('payzen_standard');
            } else {
                $oneClickQuote->getBillingAddress()->setPaymentMethod('payzen_standard');
            }

            $data = array('method' => 'payzen_standard', 'payzen_standard_use_identifier' => 1);
            $oneClickQuote->getPayment()->importData($data);

            $this->_getDataHelper()->log('Save 1-Click quote after total recollection.');
            $oneClickQuote->collectTotals()->setIsActive(true)->save();

            // Reload 1-Click quote.
            $this->getPayzenSession()->unsetQuote();
            $oneClickQuote = $this->getPayzenSession()->getQuote();

            if ($this->getCheckout()->getQuoteId()) {
                // Save current quote ID to reload it farther.
                $this->getPayzenSession()->setPayzenInitialQuoteId($this->getCheckout()->getQuoteId());
                $this->getCheckout()->getQuote()->setIsActive(false)->save();
            }

            // Save 1-Click quote to checkout session.
            $this->getPayzenSession()->setPayzenOneclickPayment(true)
                ->setPayzenOneclickBackUrl($this->_getRefererUrl());
            $this->getCheckout()->replaceQuote($oneClickQuote);

            $this->_getDataHelper()->log('Create order from 1-Click quote.');
            $service = Mage::getModel('sales/service_quote', $oneClickQuote);
            $order = $service->submit();

            $this->_getDataHelper()->log(
                'Set 1-Click payment information to session and redirect to payment page.'
            );
            $this->getCheckout()->setLastSuccessQuoteId($this->getCheckout()->getQuoteId())
                ->setLastRealOrderId($order->getIncrementId());

            $redirectUrl = Mage::getUrl('payzen/payment/form', array('_secure' => true));
            $this->_redirectUrl($redirectUrl);
        } catch (Mage_Core_Exception $e) {
            $this->_getDataHelper()->log(
                'Error when trying to pay with 1-Click. ' . $e->getMessage(),
                Zend_Log::WARN
            );

            // Disable 1-Click quote.
            $oneClickQuote = $this->getPayzenSession()->getQuote();
            $oneClickQuote->setIsActive(false)->setReservedOrderId(null)->save();

            // Restore initial checkout quote.
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

            // Use core/session instance to be able to show messages from all pages.
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
                // Remove all 1-Click quote items to refresh it.
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
                        // Error message.
                        $this->getCoreSession()->setUseNotice(true);
                        Mage::throwException($result);
                    }
                } catch (Mage_Core_Exception $e) {
                    Mage::throwException($e->getMessage());
                } catch (Exception $e) {
                    Mage::throwException(
                        $this->__('Cannot pay requested product with &laquo; PayZen Buy now &raquo;.')
                    );
                }

                // Related products.
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
        // Clear all messages from session.
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
        // Clear all messages in session.
        $this->getCheckout()->getMessages(true);
        $this->getCoreSession()->getMessages(true);

        $this->_getDataHelper()->log("Redirecting to failure page for order #{$order->getIncrementId()}.");
        $this->_redirect('checkout/onepage/failure', array('_store' => $order->getStore()->getId()));
    }

    /**
     * Redirect to result page (according to payment status).
     *
     * @param $order
     * @param $case
     * @param $checkUrlWarn
     */
    public function redirectResponse($order, $case, $checkUrlWarn = false)
    {
        // Clear all messages in session.
        $this->getCheckout()->getMessages(true);
        $this->getCoreSession()->getMessages(true);

        $storeId = $order->getStore()->getId();
        if ($this->_getDataHelper()->getCommonConfigData('ctx_mode', $storeId) === 'TEST') {
            if (Lyranetwork_Payzen_Helper_Data::$pluginFeatures['prodfaq']) {
                // Display going to production message.
                $message = $this->__('<b>GOING INTO PRODUCTION:</b> You want to know how to put your shop into production mode, please read chapters &laquo; Proceeding to test phase &raquo; and &laquo; Shifting the shop to production mode &raquo; in the documentation of the module.');
                $this->getCoreSession()->addNotice($message);
            }

            if ($checkUrlWarn) {
                // Order not validated by notification URL, in TEST mode, user is webmaster.
                // So display a warning about notification URL not working.

                if ($this->_getDataHelper()->isMaintenanceMode()) {
                    $message = $this->__('The shop is in maintenance mode.The automatic notification cannot work.');
                } else {
                    $message = $this->__('The automatic validation has not worked. Have you correctly set up the notification URL in your PayZen Back Office?');
                    $message .= '<br /><br />';
                    $message .= $this->__('For understanding the problem, please read the documentation of the module:<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo; To read carefully before going further &raquo;<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo; Notification URL settings &raquo;');
                }

                $this->getCoreSession()->addError($message);
            }
        }

        // Was this a 1-Click payment?
        $oneclick = $this->getPayzenSession()->getPayzenOneclickPayment(true);

        if ($case === Lyranetwork_Payzen_Helper_Payment::SUCCESS) {
            if ($oneclick) {
                $this->getPayzenSession()->unsetAll();
            }

            $this->getCheckout()->setLastQuoteId($order->getQuoteId())
                ->setLastSuccessQuoteId($order->getQuoteId())
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            $this->_getDataHelper()->log("Redirecting to success page for order #{$order->getIncrementId()}.");
            $this->_redirect('checkout/onepage/success', array('_store' => $storeId));
        } else {
            $this->_getDataHelper()->log("Unsetting order data in session for order #{$order->getIncrementId()}.");
            $this->getCheckout()->setLastBillingAgreementId(null)
                ->setRedirectUrl(null)
                ->setLastOrderId(null)
                ->setLastRealOrderId(null)
                ->setLastRecurringProfileIds(null)
                ->setAdditionalMessages(null);

            if ($case === Lyranetwork_Payzen_Helper_Payment::FAILURE) {
                $this->getCoreSession()->addWarning($this->__('Your payment was not accepted. Please, try to re-order.'));
            }

            if ($oneclick) {
                $oneClickQuote = $this->getPayzenSession()->getQuote();
                $oneClickQuote->setReservedOrderId(null)->save();
                $this->getPayzenSession()->unsetQuote();

                $this->getPayzenSession()->unsPayzenInitialQuoteId();

                // In case of 1-Click payment , redirect to referer URL.
                $this->_getDataHelper()->log(
                    "Redirecting to referer URL (product view or cart page) for order #{$order->getIncrementId()}."
                );
                $this->_redirectUrl($this->getPayzenSession()->getPayzenOneclickBackUrl(true));
            } else {
                $this->_getDataHelper()->log("Restore cart for order #{$order->getIncrementId()} to allow re-order quicker.");

                $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                if ($quote->getId()) {
                    $quote->setIsActive(true)->setReservedOrderId(null)->save();
                    $this->getCheckout()->replaceQuote($quote);
                }

                $this->_getDataHelper()->log("Redirecting to cart page for order #{$order->getIncrementId()}.");
                $this->_redirect('checkout/cart', array('_store' => $storeId));
            }
        }
    }

    /**
     * Set redirect into response.
     *
     * @param  string $path
     * @param  array  $arguments
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
     * Get 1-Click checkout session namespace.
     *
     * @return Lyranetwork_Payzen_Model_Session
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
     * Return data helper.
     *
     * @return Lyranetwork_Payzen_Helper_Data
     */
    protected function _getDataHelper()
    {
        return Mage::helper('payzen');
    }

    /**
     * Return payment helper.
     *
     * @return Mage_Payzen_Helper_Payment
     */
    protected function _getPaymentHelper()
    {
        return Mage::helper('payzen/payment');
    }
}
