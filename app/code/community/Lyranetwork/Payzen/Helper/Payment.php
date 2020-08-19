<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Helper_Payment extends Mage_Core_Helper_Abstract
{
    const IDENTIFIER = 'payzen_identifier'; // Key to save if payment is by identifier.
    const MULTI_OPTION = 'payzen_multi_option'; // Key to save choosen multi option.
    const TOKEN_DATA = 'payzen_token_data'; // Key to save payment token data.
    const TOKEN = 'payzen_token'; // Key to save payment token.

    const RISK_CONTROL = 'payzen_risk_control'; // Key to save risk control results.
    const RISK_ASSESSMENT = 'payzen_risk_assessment'; // Key to save risk assessment results.
    const ALL_RESULTS = 'payzen_all_results'; // Key to save risk assessment results.
    const TRANS_UUID = 'payzen_trans_uuid';
    const BRAND_USER_CHOICE = 'payzen_brand_user_choice';

    const ONECLICK_LOCATION_CART = 'CART';
    const ONECLICK_LOCATION_PRODUCT = 'PRODUCT';
    const ONECLICK_LOCATION_BOTH = 'BOTH';

    const SUCCESS = 1;
    const FAILURE = 2;
    const CANCEL = 3;

    public function doPaymentForm($controller)
    {
        // Load order.
        $lastIncrementId = $controller->getCheckout()->getLastRealOrderId();

        // Check that there is an order to pay.
        if (empty($lastIncrementId)) {
            $this->_getHelper()->log(
                "No order to be paid. It may be a direct access to redirection form page."
                . " [IP = {$this->_getHelper()->getIpAddress()}]."
            );
            $controller->redirectBack('Order not found in session.');
            return;
        }

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($lastIncrementId);

        // Check that there is products in cart.
        if ($order->getTotalDue() <= 0) {
            $this->_getHelper()->log(
                "Payment attempt with no amount. [Order = {$order->getIncrementId()}]" .
                " [IP = {$this->_getHelper()->getIpAddress()}]."
            );
            $controller->redirectBack('Order total is empty.');
            return;
        }

        // Check that order is not processed yet.
        if (! $controller->getCheckout()->getLastSuccessQuoteId()) {
            $this->_getHelper()->log(
                "Payment attempt with a quote already processed." .
                " [Order = {$order->getIncrementId()}] [IP = {$this->_getHelper()->getIpAddress()}]."
            );
            $controller->redirectBack('Order payment already processed.');
            return;
        }

        // Add history comment and save it.
        $order->addStatusHistoryComment(
            $this->_getHelper()->__('Client sent to PayZen gateway.'),
            false
        )->save();

        // Clear quote data.
        $controller->getCheckout()->setQuoteId(null);
        $controller->getCheckout()->setLastSuccessQuoteId(null);

        // Inactivate quote.
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(false)->save();
        }

        // Redirect to gateway.
        $this->_getHelper()->log("Display payment form and JavaScript for order #{$order->getIncrementId()}.");

        $controller->loadLayout();
        $controller->renderLayout();

        $this->_getHelper()->log(
            "Client {$order->getCustomerEmail()} sent to payment page for order #{$order->getIncrementId()}."
        );
    }

    public function doPaymentReturn($controller)
    {
        $request = $controller->getRequest()->getParams();

        // Loading order.
        $orderId = key_exists('vads_order_id', $request) ? $request['vads_order_id'] : 0;
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);

        // Get store id from order.
        $storeId = $order->getStore()->getId();

        // Load API response.
        $response = new Lyranetwork_Payzen_Model_Api_Response(
            $request,
            $this->_getHelper()->getCommonConfigData('ctx_mode', $storeId),
            $this->_getHelper()->getCommonConfigData('key_test', $storeId),
            $this->_getHelper()->getCommonConfigData('key_prod', $storeId),
            $this->_getHelper()->getCommonConfigData('sign_algo', $storeId)
        );

        if (! $response->isAuthentified()) {
            // Authentification failed.
            $ip = $this->_getHelper()->getIpAddress();

            $this->_getHelper()->log(
                "{$ip} tries to access payzen/payment/return page without valid signature with parameters: " . print_r($request, true),
                Zend_Log::ERR
            );
            $this->_getHelper()->log(
                'Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.',
                Zend_Log::ERR
            );

            $controller->redirectError($order);
            return;
        }

        if (! $orderId) {
            $this->_getHelper()->log(
                "Order ID not returned. Payment result: {$response->getLogMessage()}",
                Zend_Log::ERR
            );
            $controller->redirectError($order);
            return;
        }

        $this->_getHelper()->log("Request authenticated for order #{$order->getIncrementId()}.");

        if ($order->getStatus() === 'pending_payment') {
            // Order waiting for payment.
            $this->_getHelper()->log("Order #{$order->getIncrementId()} is waiting for payment.");

            if ($this->_isPaymentSuccessfullyProcessed($response)) {
                $this->_getHelper()->log(
                    "Payment for order #{$order->getIncrementId()} has been confirmed by client return!" .
                    " This means the notification URL did not work.",
                    Zend_Log::WARN
                );

                // Save order and optionally create invoice.
                $this->_registerOrder($order, $response);

                // Display success page.
                $controller->redirectResponse($order, self::SUCCESS, true /* IPN URL warn in TEST mode */);
            } else {
                $this->_getHelper()->log("Payment for order #{$order->getIncrementId()} has failed.");

                // Cancel order.
                $this->_cancelOrder($order, $response);

                // Redirect to cart page.
                $case = $response->isCancelledPayment() ? self::CANCEL : self::FAILURE;
                $controller->redirectResponse($order, $case);
            }
        } else {
            // Payment already processed.
            $this->_getHelper()->log("Order #{$order->getIncrementId()} has already been processed.");

            $acceptedStatus = $this->_getHelper()->getCommonConfigData('registered_order_status', $storeId);
            $successStatuses = array(
                $acceptedStatus,
                'complete' /* case of virtual orders */,
                'payment_review' /* case of Oney (and other) pending payments */,
                'fraud' /* fraud status is taken as successful because it's just a suspicion */,
                'payzen_to_validate' /* payment will be done after manual validation */,
                'payzen_pending_transfer' /* for SEPA and SOFORT payments */
            );

            if ($this->_isPaymentSuccessfullyProcessed($response) && in_array($order->getStatus(), $successStatuses)) {
                $this->_getHelper()->log("Order #{$order->getIncrementId()} is confirmed.");
                // Clear quote data.
                $controller->getCheckout()->setQuoteId(null);
                $controller->getCheckout()->setLastSuccessQuoteId(null);

                $controller->redirectResponse($order, self::SUCCESS);
            } elseif ($order->isCanceled() && ! $this->_isPaymentSuccessfullyProcessed($response)) {
                $this->_getHelper()->log("Order #{$order->getIncrementId()} cancelation is confirmed.");

                $case = $response->isCancelledPayment() ? self::CANCEL : self::FAILURE;
                $controller->redirectResponse($order, $case);
            } else {
                // This is an error case, the client returns with an error but the payment already has been accepted.
                $this->_getHelper()->log(
                    "Order #{$order->getIncrementId()} has been validated but we receive a payment error code!",
                    Zend_Log::ERR
                );
                $controller->redirectError($order);
            }
        }
    }

    public function doPaymentCheck($controller)
    {
        $post = $controller->getRequest()->getPost();

        // Loading order.
        $orderId = key_exists('vads_order_id', $post) ? $post['vads_order_id'] : 0;
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);

        // Get store ID from order.
        $storeId = $order->getStore()->getId();

        // Init app with correct store id.
        Mage::app()->init($storeId, 'store');

        // Load API response.
        $response = new Lyranetwork_Payzen_Model_Api_Response(
            $post,
            $this->_getHelper()->getCommonConfigData('ctx_mode', $storeId),
            $this->_getHelper()->getCommonConfigData('key_test', $storeId),
            $this->_getHelper()->getCommonConfigData('key_prod', $storeId),
            $this->_getHelper()->getCommonConfigData('sign_algo', $storeId)
        );

        if (! $response->isAuthentified()) {
            $ip = $this->_getHelper()->getIpAddress();

            // Authentification failed.
            $this->_getHelper()->log(
                "{$ip} tries to access payzen/payment/check page without valid signature with parameters: " . print_r($post, true),
                Zend_Log::ERR
            );
            $this->_getHelper()->log(
                'Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.',
                Zend_Log::ERR
            );

            $controller->getResponse()->setBody($response->getOutputForPlatform('auth_fail'));
            return;
        }

        $this->_getHelper()->log("Request authenticated for order #{$order->getIncrementId()}.");

        $reviewStatuses = array('payment_review', 'payzen_to_validate', 'fraud', 'payzen_pending_transfer');
        if ($order->getStatus() === 'pending_payment' || in_array($order->getStatus(), $reviewStatuses)) {
            // Order waiting for payment.
            $this->_getHelper()->log("Order #{$order->getIncrementId()} is waiting for payment.");

            if ($this->_isPaymentSuccessfullyProcessed($response)) {
                $this->_getHelper()->log(
                    "Payment for order #{$order->getIncrementId()} has been confirmed by notification URL."
                );

                $stateObject = $this->nextOrderState($response, $order);
                if ($order->getStatus() === $stateObject->getStatus()) { // Payment status is unchanged.
                    // Display notification url confirmation message.
                    $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ok_already_done'));
                } else {
                    // Save order and optionally create invoice.
                    $this->_registerOrder($order, $response);

                    // Display notification url confirmation message.
                    $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ok'));
                }
            } else {
                $this->_getHelper()->log(
                    "Payment for order #{$order->getIncrementId()} has been invalidated by notification URL."
                );

                // Cancel order.
                $this->_cancelOrder($order, $response);

                // Display notification url failure message.
                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ko'));
                return;
            }
        } else {
            // Payment already processed.

            $acceptedStatus = $this->_getHelper()->getCommonConfigData('registered_order_status', $storeId);
            $successStatuses = array(
                $acceptedStatus,
                'complete' /* case of virtual orders */
            );

            if ($this->_isPaymentSuccessfullyProcessed($response) && in_array($order->getStatus(), $successStatuses)) {
                $this->_getHelper()->log("Order #{$order->getIncrementId()} is confirmed.");

                if ($response->get('operation_type') === 'CREDIT') {
                    // This is a refund TODO create credit memo.

                    $expiry = '';
                    if ($response->get('expiry_month') && $response->get('expiry_year')) {
                        $expiry = str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT)
                            . ' / ' . $response->get('expiry_year');
                    }

                    $transactionId = $response->get('trans_id') . '-' . $response->get('sequence_number');

                    // Save paid amount.
                    $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByNumCode($response->get('currency'));
                    $amount = number_format($currency->convertAmountToFloat($response->get('amount')), $currency->getDecimals(), ',', ' ');

                    $amountDetail = $amount . ' ' . $currency->getAlpha3();

                    if ($response->get('effective_currency') && ($response->get('currency') !== $response->get('effective_currency'))) {
                        $effectiveCurrency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByNumCode($response->get('effective_currency'));

                        $effectiveAmount = number_format(
                            $effectiveCurrency->convertAmountToFloat($response->get('effective_amount')),
                            $effectiveCurrency->getDecimals(),
                            ',',
                            ' '
                        );

                        $amountDetail = $effectiveAmount . ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
                    }

                    $additionalInfo = array(
                        'Transaction Type' => 'CREDIT',
                        'Amount' => $amountDetail,
                        'Transaction ID' => $transactionId,
                        'Transaction UUID' => $response->get('trans_uuid'),
                        'Transaction Status' => $response->getTransStatus(),
                        'Means of Payment' => $response->get('card_brand'),
                        'Card Number' => $response->get('card_number'),
                        'Expiration Date' => $expiry
                    );

                    $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND;

                    $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
                } else {
                    // Update transaction info.
                    $this->updatePaymentInfo($order, $response);
                }

                $order->save();

                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ok_already_done'));
            } elseif ($order->isCanceled() && ! $this->_isPaymentSuccessfullyProcessed($response)) {
                $this->_getHelper()->log("Order #{$order->getIncrementId()} cancelation is confirmed.");
                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ko_already_done'));
            } else {
                // This is an error case, the client returns with an error but the payment already has been accepted.
                $this->_getHelper()->log(
                    "Order #{$order->getIncrementId()} has been validated but we receive a payment error code!",
                    Zend_Log::ERR
                );
                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ko_on_order_ok'));
            }
        }
    }

    public function doPaymentRestReturn($controller)
    {
        $rawData = $controller->getRequest()->getParams();

        if (! $this->_getRestHelper()->checkResponseFormat($rawData)) {
            $this->_getHelper()->log(
                'Invalid return request received, redirect to home page. Content: ' . print_r($rawData, true),
                Zend_Log::ERR
             );
            $controller->getResponse()->setRedirect(Mage::getBaseUrl());
            return;
        }

        $test = $this->_getHelper()->getCommonConfigData('ctx_mode') === 'TEST';
        $returnKey = $this->_getRestHelper()->getReturnKey($test);

        if (! $this->_getRestHelper()->checkResponseHash($rawData, $returnKey)) {
            // authentication failed
            $ip = $this->_getHelper()->getIpAddress();

            $this->_getHelper()->log(
                "{$ip} tries to access payzen/payment/restReturn page without valid signature.",
                Zend_Log::ERR
            );
            $controller->getResponse()->setRedirect(Mage::getBaseUrl());
            return;
        }

        $answer = json_decode($rawData['kr-answer'], true);
        if (! is_array($answer)) {
            $this->_getHelper()->log(
                'Invalid return request received, redirect to home page. Content of kr-answer: ' . $rawData['kr-answer'],
                Zend_Log::ERR
            );
            $controller->getResponse()->setRedirect(Mage::getBaseUrl());
            return;
        }

        // Wrap payment result to use traditional order creation tunnel.
        $data = $this->_getRestHelper()->convertRestResult($answer);

        /** @var Lyranetwork_Payzen_Model_Api_Response $response */
        $response = new Lyranetwork_Payzen_Model_Api_Response($data, null, null, null);

        $quoteId = (int) $response->getExtInfo('quote_id'); // Quote ID is sent to platform as ext_info.
        $quote = Mage::getModel('sales/quote');
        $quote->load($quoteId);

        if (! $quote->getId()) {
            $this->_getHelper()->log("Quote #$quoteId not found in database. Redirect to home page.");
            $controller->getResponse()->setRedirect(Mage::getBaseUrl());
            return;
        }

        // Clear quote data.
        $quote->getPayment()->unsAdditionalInformation(self::TOKEN_DATA);
        $quote->getPayment()->unsAdditionalInformation(self::TOKEN);
        $quote->getPayment()->unsAdditionalInformation(self::TOKEN_DATA . '_identifier');
        $quote->getPayment()->unsAdditionalInformation(self::TOKEN . '_identifier');

        $quote->setIsActive(false)->save();

        $this->_getHelper()->log("Request authenticated for quote #{$quote->getId()}.");

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($quote->getReservedOrderId());

        if (! $order->getId()) {
            $quote->setTotalsCollectedFlag(true);
            $this->_getOnepageForQuote($quote)->saveOrder();
            $order->loadByIncrementId($quote->getReservedOrderId());

            $this->_getHelper()->log("Order #{$order->getIncrementId()} has been created for quote #{$quoteId}.");
        } else {
            $this->_getHelper()->log("Found order #{$order->getIncrementId()} for quote #{$quoteId}.");
        }

        // Get store id from order.
        $storeId = $order->getStore()->getId();

        if ($order->getStatus() === 'pending_payment') {
            // Order waiting for payment.
            $this->_getHelper()->log("Order #{$order->getIncrementId()} is waiting for payment.");

            if ($this->_isPaymentSuccessfullyProcessed($response)) {
                $this->_getHelper()->log(
                    "Payment for order #{$order->getIncrementId()} has been confirmed by client return!" .
                    " This means the notification URL did not work.",
                    Zend_Log::WARN
                );

                // Save order and optionally create invoice.
                $this->_registerOrder($order, $response);

                // Display success page.
                $controller->redirectResponse($order, self::SUCCESS, true /* IPN URL warn in TEST mode */);
            } else {
                $this->_getHelper()->log("Payment for order #{$order->getIncrementId()} has failed.");

                // Cancel order.
                $this->_cancelOrder($order, $response);

                // Redirect to cart page.
                $case = $response->isCancelledPayment() ? self::CANCEL : self::FAILURE;
                $controller->redirectResponse($order, $case);
            }
        } else {
            // Payment already processed.
            $this->_getHelper()->log("Order #{$order->getIncrementId()} has already been processed.");

            $acceptedStatus = $this->_getHelper()->getCommonConfigData('registered_order_status', $storeId);
            $successStatuses = array(
                 $acceptedStatus,
                'complete' /* case of virtual orders */,
                'payment_review' /* case of Oney (and other) pending payments */,
                'fraud' /* fraud status is taken as successful because it's just a suspicion */,
                'payzen_to_validate' /* payment will be done after manual validation */,
                'payzen_pending_transfer' /* for SEPA and SOFORT payments */
            );

            if ($this->_isPaymentSuccessfullyProcessed($response) && in_array($order->getStatus(), $successStatuses)) {
                $this->_getHelper()->log("Order #{$order->getIncrementId()} is confirmed.");

                $controller->redirectResponse($order, self::SUCCESS);
            } elseif ($order->isCanceled() && ! $this->_isPaymentSuccessfullyProcessed($response)) {
                $this->_getHelper()->log("Order #{$order->getIncrementId()} cancelation is confirmed.");

                $case = $response->isCancelledPayment() ? self::CANCEL : self::FAILURE;
                $controller->redirectResponse($order, $case);
            } else {
                // This is an error case, the client returns with an error but the payment already has been accepted.
                $this->_getHelper()->log(
                    "Order #{$order->getIncrementId()} has been validated but we receive a payment error code!",
                    Zend_Log::ERR
                );
                $controller->redirectError($order);
             }
        }
    }

    public function doPaymentRestCheck($controller)
    {
        $post = $controller->getRequest()->getPost();

        if (! $this->_getRestHelper()->checkResponseFormat($post)) {
            $this->_getHelper()->log(
                'Invalid IPN request received. Content: ' . print_r($post, true),
                Zend_Log::ERR
            );
            $controller->getResponse()->setBody('<span style="display:none">KO-Invalid IPN request received.'."\n".'</span>');
            return;
        }

        $answer = json_decode($post['kr-answer'], true);
        if (! is_array($answer)) {
            $this->_getHelper()->log(
                'Invalid IPN request received. Content of kr-answer: ' . $post['kr-answer'],
                Zend_Log::ERR
            );
            $controller->getResponse()->setBody('<span style="display:none">KO-Invalid IPN request received.'."\n".'</span>');
            return;
        }

        $test = $this->_getHelper()->getCommonConfigData('ctx_mode') === 'TEST';
        $privateKey = $this->_getPassword($test);

        if (! $this->_getRestHelper()->checkResponseHash($post, $privateKey)) {
            // Authentication failed.
            $ip = $this->_getHelper()->getIpAddress();

            $this->_getHelper()->log(
                "{$ip} tries to access payzen/payment/restCheck page without valid signature.",
                Zend_Log::ERR
            );
            $controller->getResponse()->setBody('<span style="display:none">KO-An error occurred while computing the signature.'."\n".'</span>');
            return;
        }

        // Wrap payment result to use traditional order creation tunnel.
        $data = $this->_getRestHelper()->convertRestResult($answer);
        $response = new Lyranetwork_Payzen_Model_Api_Response($data, null, null, null);

        $quoteId = (int) $response->getExtInfo('quote_id'); // Quote ID is sent to platform as ext_info.
        $quote = Mage::getModel('sales/quote');
        $quote->load($quoteId);

        if (! $quote->getId()) {
            $this->_getHelper()->log("Quote #$quoteId not found in database.");
            $controller->getResponse()->setBody($response->getOutputForPlatform('order_not_found'));
            return;
        }

        // Case of failure when retries are enabled, do nothing before last attempt.
        if (! $response->isAcceptedPayment() && ($answer['orderCycle'] !== 'CLOSED')) {
            $this->_getHelper()->log("Payment is not accepted but buyer can try to re-order. Do not create order at this time. Quote ID: #{$quoteId},
                    reserved order ID: #{$quote->getReservedOrderId()}.");
            $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ko_bis'));
            return;
        }

        // Clear quote data.
        $quote->getPayment()->unsAdditionalInformation(self::TOKEN_DATA);
        $quote->getPayment()->unsAdditionalInformation(self::TOKEN);
        $quote->getPayment()->unsAdditionalInformation(self::TOKEN_DATA . '_identifier');
        $quote->getPayment()->unsAdditionalInformation(self::TOKEN . '_identifier');

        // Get store id from quote.
        $storeId = $quote->getStore()->getId();

        // Init app with correct store ID.
        Mage::app()->init($storeId, 'store');

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($quote->getReservedOrderId());

        if (! $order->getId()) {
            $quote->setTotalsCollectedFlag(true);
            $this->_getOnepageForQuote($quote)->saveOrder();
            $order->loadByIncrementId($quote->getReservedOrderId());

            $this->_getHelper()->log("Order #{$order->getIncrementId()} has been created for quote #{$quoteId}.");
        } else {
            $this->_getHelper()->log("Found order #{$order->getIncrementId()} for quote #{$quoteId}.");
        }

        $reviewStatuses = array('payment_review', 'payzen_to_validate', 'fraud');
        if (($order->getStatus() === 'pending_payment') || $order->isCanceled() || in_array($order->getStatus(), $reviewStatuses)) {
            // Order waiting for payment.
            $this->_getHelper()->log("Try to save payment result for order #{$order->getIncrementId()}.");

            if ($response->isAcceptedPayment()) {
                $this->_getHelper()->log(
                    "Payment for order #{$order->getIncrementId()} has been confirmed by notification URL."
                );

                $stateObject = $this->nextOrderState($response, $order);
                if ($order->getStatus() === $stateObject->getStatus()) { // Payment status is unchanged.
                    // Display notification URL confirmation message.
                    $this->_getHelper()->log("Order #{$order->getIncrementId()} is confirmed.");
                    $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ok_already_done'));
                } else {
                    // Save order and optionally create invoice.
                    $this->_registerOrder($order, $response);

                    // Display notification URL confirmation message.
                    $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ok'));
                }
            } else {
                $this->_getHelper()->log(
                    "Payment for order #{$order->getIncrementId()} has been invalidated by notification URL."
                );

                // Cancel order.
                $this->_cancelOrder($order, $response);

                // Display notification URL failure message.
                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ko'));
            }
        } else {
            // Payment already processed.

            $acceptedStatus = $this->_getHelper()->getCommonConfigData('registered_order_status', $storeId);
            $successStatuses = array(
                $acceptedStatus,
                'complete' /* Case of virtual orders. */
            );

            if ($response->isAcceptedPayment() && in_array($order->getStatus(), $successStatuses)) {
                $this->_getHelper()->log("Order #{$order->getIncrementId()} is confirmed.");

                // Update transaction info.
                $this->updatePaymentInfo($order, $response);
                $order->save();

                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ok_already_done'));
            } elseif ($order->isCanceled() && ! $response->isAcceptedPayment()) {
                $this->_getHelper()->log("Order #{$order->getIncrementId()} cancelation is confirmed.");
                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ko_already_done'));
            } else {
                // This is an error case, the client returns with an error but the payment already has been accepted.
                $this->_getHelper()->log(
                    "Order #{$order->getIncrementId()} has been validated but we receive a payment error code!",
                    Zend_Log::ERR
                );
                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ko_on_order_ok'));
            }
        }
    }

    private function _isPaymentSuccessfullyProcessed(Lyranetwork_Payzen_Model_Api_Response $response)
    {
        if ($response->isAcceptedPayment()) {
            return true;
        }

        return $response->get('identifier') && in_array($response->get('identifier_status'), array('CREATED', 'UPDATED'));
    }

    /**
     * Update order status and eventually create invoice.
     *
     * @param Mage_Sales_Model_Order $order
     * @param Lyranetwork_Payzen_Model_Api_Response $response
     */
    protected function _registerOrder(Mage_Sales_Model_Order $order, Lyranetwork_Payzen_Model_Api_Response $response)
    {
        $this->_getHelper()->log("Saving payment for order #{$order->getIncrementId()}.");

        // Update authorized amount.
        $order->getPayment()->setAmountAuthorized($order->getTotalDue());
        $order->getPayment()->setBaseAmountAuthorized($order->getBaseTotalDue());

        // Retrieve new order state and status.
        $stateObject = $this->nextOrderState($response, $order);
        $this->_getHelper()->log(
            "Order #{$order->getIncrementId()}, new state: {$stateObject->getState()}"
            . ", new status: {$stateObject->getStatus()}."
        );

        $order->setState($stateObject->getState(), $stateObject->getStatus(), $response->getMessage());
        if ($stateObject->getState() === Mage_Sales_Model_Order::STATE_HOLDED) { // For magento 1.4.0.x
            $order->setHoldBeforeState($stateObject->getBeforeState());
            $order->setHoldBeforeStatus($stateObject->getBeforeStatus());
        }

        // Save gateway responses.
        $this->updatePaymentInfo($order, $response);

        // Try to save gateway identifier if any.
        $method = $order->getPayment()->getMethodInstance();
        if ($method instanceof Lyranetwork_Payzen_Model_Payment_Sepa) {
            // Payment made with SEPA method.
            $this->_saveSepaIdentifier($order, $response);
         }  else {
            // Try to save gateway identifier if any.
            $this->_saveIdentifier($order, $response);
        }

        // Try to create invoice.
        $this->createInvoice($order);

        $this->_getHelper()->log("Saving confirmed order #{$order->getIncrementId()} and sending e-mail if not disabled.");
        $order->save();

        $sendEmail = true;
        if ($info = $response->get('order_info')) {
            $sendEmail = (bool) substr($info, strlen('send_confirmation='));
        }

        if ($sendEmail) {
            $order->sendNewOrderEmail();
        }
    }

    /**
     * Update order payment information.
     *
     * @param Mage_Sales_Model_Order $order
     * @param Lyranetwork_Payzen_Model_Api_Response|Lyranetwork_Payzen_Model_Api_Ws_ResultWrapper $response
     */
    public function updatePaymentInfo(Mage_Sales_Model_Order $order, $response)
    {
        // Set common payment information.
        $order->getPayment()->setCcTransId($response->get('trans_id'))
            ->setCcType($response->get('card_brand'))
            ->setCcStatus($response->getResult())
            ->setCcStatusDescription($response->getMessage())
            ->setAdditionalInformation(self::ALL_RESULTS, serialize($response->getAllResults()))
            ->setAdditionalInformation(self::TRANS_UUID, $response->get('trans_uuid'));

        if ($response->isCancelledPayment()) {
            // No more data to save.
            return;
        }

        if ($response->get('brand_management')) {
            $brandInfo = Mage::helper('core')->jsonDecode($response->get('brand_management'), Zend_Json::TYPE_OBJECT);

            $userChoice = (isset($brandInfo->userChoice) && $brandInfo->userChoice);
            $order->getPayment()->setAdditionalInformation(self::BRAND_USER_CHOICE, $userChoice);
        }

        // Save risk control result if any.
        $riskControl = $response->getRiskControl();
        if (! empty($riskControl)) {
            $order->getPayment()->setAdditionalInformation(self::RISK_CONTROL, $riskControl);
        }

        // Save risk assessment result if any.
        $riskAssessment = $response->getRiskAssessment();
        if (! empty($riskAssessment)) {
            $order->getPayment()->setAdditionalInformation(self::RISK_ASSESSMENT, $riskAssessment);
        }

        // Set is_fraud_detected flag.
        $order->getPayment()->setIsFraudDetected($response->isSuspectedFraud());

        $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByNumCode($response->get('currency'));

        if ($response->get('card_brand') === 'MULTI') { // Multi brand.
            $data = Mage::helper('core')->jsonDecode($response->get('payment_seq'), Zend_Json::TYPE_OBJECT);
            $transactions = $data->{'transactions'};

            // Save transaction details to sales_payment_transaction.
            foreach ($transactions as $trs) {
                // Save transaction details to sales_payment_transaction.
                $expiry = '';
                if (! empty($trs->{'expiry_month'}) && ! empty($trs->{'expiry_year'})) {
                    $expiry = str_pad($trs->{'expiry_month'}, 2, '0', STR_PAD_LEFT) . ' / ' . $trs->{'expiry_year'};
                }

                $transactionId = $response->get('trans_id') . '-' . $trs->{'sequence_number'};

                // Save paid amount.
                $amount = number_format($currency->convertAmountToFloat($trs->{'amount'}), $currency->getDecimals(), ',', ' ');
                $amountDetail = $amount . ' ' . $currency->getAlpha3();

                $additionalInfo = array(
                    'Transaction Type' => $trs->{'operation_type'},
                    'Amount' => $amountDetail,
                    'Transaction ID' => $transactionId,
                    'Transaction UUID' => $trs->{'trans_uuid'},
                    'Extra Transaction ID' => property_exists($trs, 'ext_trans_id')
                        && isset($trs->{'ext_trans_id'}) ? $trs->{'ext_trans_id'} : '',
                    'Transaction Status' => $trs->{'trans_status'},
                    'Means of Payment' => $trs->{'card_brand'},
                    'Card Number' => $trs->{'card_number'},
                    'Expiration Date' => $expiry
                );

                $transactionType = $this->convertTxnType($trs->{'trans_status'});
                if ($transactionType) {
                    $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
                }
            }
        } else {
            // 3DS authentication result
            $threedsCavv = '';
            if (in_array($response->get('threeds_status'), array('Y', 'YES'))) {
                $threedsCavv = $response->get('threeds_cavv');
            }

            // Save payment infos to sales_flat_order_payment.
            $order->getPayment()->setCcExpMonth($response->get('expiry_month'))
                ->setCcExpYear($response->get('expiry_year'))
                ->setCcNumberEnc($response->get('card_number'))
                ->setCcSecureVerify($threedsCavv);

            // Format card expiration data.
            $expiry = '';
            if ($response->get('expiry_month') && $response->get('expiry_year')) {
                $expiry = str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT) . ' / '
                    . $response->get('expiry_year');
            }

            // Magento transaction type.
            $transactionType = $this->convertTxnType($response->getTransStatus());

            // Total payment amount and presentation date.
            $totalAmount = $response->get('amount');
            $firstDate = strtotime($response->get('presentation_date') . ' UTC');

            $option = @unserialize($order->getPayment()->getAdditionalData()); // Get choosen payment option if any.
            if ($response->get('sequence_number') == 1 && (stripos($order->getPayment()->getMethod(), 'payzen_multi') === 0)
                && is_array($option) && ! empty($option)) {
                $count = (int) $option['count'];

                // First payment of payment in installments.
                if (isset($option['first']) && $option['first']) {
                    $firstAmount = round($totalAmount * $option['first'] / 100);
                } else {
                    $firstAmount = round($totalAmount / $count);
                }

                // Double cast to avoid rounding.
                $installmentAmount = (int) (string) (($totalAmount - $firstAmount) / ($count - 1));

                for ($i = 1; $i <= $count; $i++) {
                    $transactionId = $response->get('trans_id') . '-' . $i;

                    $delay = (int) $option['period'] * ($i - 1);
                    $date = strtotime("+$delay days", $firstDate);

                    switch(true) {
                        case($i === 1): // First transaction.
                            $amount = $firstAmount;
                            break;

                        case($i === $count): // Last transaction.
                            $amount = $totalAmount - $firstAmount - $installmentAmount * ($i - 2);
                            break;

                        default: // Others.
                            $amount = $installmentAmount;
                            break;
                    }

                    $floatAmount = number_format($currency->convertAmountToFloat($amount), $currency->getDecimals(), ',', ' ');
                    $amountDetail = $floatAmount . ' ' . $currency->getAlpha3();

                    if (($rate = $response->get('change_rate')) && $response->get('effective_currency')
                        && ($response->get('currency') !== $response->get('effective_currency'))
                    ) {
                        // Effective amount.
                        $effectiveCurrency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByNumCode($response->get('effective_currency'));

                        $effectiveAmount= number_format(
                            $effectiveCurrency->convertAmountToFloat((int) ($amount / $rate)),
                            $effectiveCurrency->getDecimals(),
                            ',',
                            ' '
                        );

                        $amountDetail = $effectiveAmount . ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
                    }

                    $additionalInfo = array(
                        'Transaction Type' => $response->get('operation_type'),
                        'Amount' => $amountDetail,
                        'Presentation Date' => Mage::helper('core')->formatDate(date('Y-m-d', $date)),
                        'Transaction ID' => $transactionId,
                        'Transaction UUID' =>  ($i === 1) ? $response->get('trans_uuid') : '',
                        'Transaction Status' => ($i === 1) ? $response->getTransStatus() :
                            $this->getNextInstallmentsTransStatus($response->getTransStatus()),
                        'Means of Payment' => $response->get('card_brand'),
                        'Card Number' => $response->get('card_number'),
                        'Expiration Date' => $expiry,
                        '3DS Authentication' => $threedsCavv
                    );

                    if ($transactionType) {
                        $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
                    }
                }
            } else {
                // Save transaction details to sales_payment_transaction.
                $transactionId = $response->get('trans_id') . '-' . $response->get('sequence_number');

                $amount = number_format($currency->convertAmountToFloat($totalAmount), $currency->getDecimals(), ',', ' ');
                $amountDetail = $amount . ' ' . $currency->getAlpha3();

                if ($response->get('effective_currency') && ($response->get('currency') !== $response->get('effective_currency'))) {
                    // Effective amount.
                    $effectiveCurrency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByNumCode($response->get('effective_currency'));

                    $effectiveAmount = number_format(
                        $effectiveCurrency->convertAmountToFloat($response->get('effective_amount')),
                        $effectiveCurrency->getDecimals(),
                        ',',
                        ' '
                    );

                    $amountDetail = $effectiveAmount . ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
                }

                $additionalInfo = array(
                    'Transaction Type' => $response->get('operation_type'),
                    'Amount' => $amountDetail,
                    'Presentation Date' => Mage::helper('core')->formatDate(date('Y-m-d', $firstDate)),
                    'Transaction ID' => $transactionId,
                    'Transaction UUID' => $response->get('trans_uuid'),
                    'Transaction Status' => $response->getTransStatus(),
                    'Means of Payment' => $response->get('card_brand'),
                    'Card Number' => $response->get('card_number'),
                    'Expiration Date' => $expiry,
                    '3DS Authentication' => $threedsCavv
                );

                if ($transactionType) {
                    $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
                }
            }
        }

        // Skip automatic transaction creation.
        $order->getPayment()->setTransactionId(null)->setSkipTransactionCreation(true);
    }

    private function _saveIdentifier(Mage_Sales_Model_Order $order, Lyranetwork_Payzen_Model_Api_Response $response)
    {
        if (! $order->getCustomerId()) {
            return;
        }

        if ($response->get('identifier') && in_array($response->get('identifier_status'), array('CREATED', 'UPDATED'))) {
            $customer = Mage::getModel('customer/customer');
            $customer->load($order->getCustomerId());

            $this->_getHelper()->log(
                "Identifier for customer #{$customer->getId()} successfully "
                . "created or updated on payment gateway. Let's save it to customer entity."
            );

            $customer->setData('payzen_identifier', $response->get('identifier'));

            // Mask all card digits unless the last 4 ones.
            $number = $response->get('card_number');
            $masked = '';

            $matches = array();
            if (preg_match('#^([A-Z]{2}[0-9]{2}[A-Z0-9]{10,30})(_[A-Z0-9]{8,11})?$#i', $number, $matches)) {
                // IBAN(_BIC).
                $masked .= isset($matches[2]) ? str_replace('_', '', $matches[2]) . '/' : ''; // BIC

                $iban = $matches[1];
                $masked .= substr($iban, 0, 4) . str_repeat('X', strlen($iban) - 8) . substr($iban, -4);
            } elseif (strlen($number) > 4) {
                $masked = str_repeat('X', strlen($number) - 4) . substr($number, -4);

                if ($response->get('expiry_month') && $response->get('expiry_year')) {
                    // Format card expiration data.
                    $masked .= ' ';
                    $masked .= str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT);
                    $masked .= '/';
                    $masked .= $response->get('expiry_year');
                }
            }

            $customer->setData('payzen_masked_card', $masked);

            $customer->save();

            $this->_getHelper()->log(
                "Identifier for customer #{$customer->getId()} successfully saved to customer entity."
            );
        }
    }

    private function _saveSepaIdentifier(Mage_Sales_Model_Order $order, Lyranetwork_Payzen_Model_Api_Response $response)
    {
        if (! $order->getCustomerId()) {
            return;
        }

        if ($response->get('identifier') && in_array($response->get('identifier_status'), array('CREATED', 'UPDATED'))) {
            $customer = Mage::getModel('customer/customer');
            $customer->load($order->getCustomerId());

            $this->_getHelper()->log(
                "SEPA identifier for customer #{$customer->getId()} successfully "
                . "created or updated on payment gateway. Let's save it to customer entity."
            );

            $customer->setData('payzen_sepa_identifier', $response->get('identifier'));

            // Mask all card digits unless the last 4 ones.
            $number = $response->get('card_number');
            $masked = '';

            $matches = array();
            if (preg_match('#^([A-Z]{2}[0-9]{2}[A-Z0-9]{10,30})(_[A-Z0-9]{8,11})?$#i', $number, $matches)) {
                // IBAN(_BIC).
                $masked .= isset($matches[2]) ? str_replace('_', '', $matches[2]) . '/' : ''; // BIC

                $iban = $matches[1];
                $masked .= substr($iban, 0, 4) . str_repeat('X', strlen($iban) - 8) . substr($iban, -4);
            }

            $customer->setData('payzen_sepa_iban', $masked);

            $customer->save();

            $this->_getHelper()->log(
                "SEPA identifier for customer #{$customer->getId()} successfully saved to customer entity."
            );
        }
    }

    public function createInvoice(Mage_Sales_Model_Order $order)
    {
        // Flag that is true if automatically create invoice.
        $autoCapture = $this->_getHelper()->getCommonConfigData('capture_auto', $order->getStore()->getId());

        if (! $autoCapture || ($order->getState() !== 'processing') || ! $order->canInvoice()) {
            // Creating invoice not allowed.
            return;
        }

        $this->_getHelper()->log("Creating invoice for order #{$order->getIncrementId()}.");

        // Convert order to invoice.
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
        $invoice->setTransactionId($order->getPayment()->getLastTransId());
        $invoice->register()->save();
        $order->addRelatedObject($invoice);

        // Add history entry.
        $message = $this->_getHelper()->__('Invoice %s was created.', $invoice->getIncrementId());
        $order->addStatusHistoryComment($message);
    }

    /**
     * Cancel order.
     *
     * @param Mage_Sales_Model_Order         $order
     * @param Lyranetwork_Payzen_Model_Api_Response $response
     */
    protected function _cancelOrder(Mage_Sales_Model_Order $order, Lyranetwork_Payzen_Model_Api_Response $response)
    {
        $this->_getHelper()->log("Canceling order #{$order->getIncrementId()}.");

        $order->registerCancellation($response->getMessage());

        // Save gateway responses.
        $this->updatePaymentInfo($order, $response);
        $order->save();

        Mage::dispatchEvent('order_cancel_after', array('order' => $order));
    }

    /**
     * Public access to Mage_Sales_Model_Order_Payment::_addTransaction method to let it available for magento 1.4.0.1.
     *
     * @param  Mage_Sales_Model_Order_Payment $payment
     * @param  string $type
     * @param  string $transactionId
     * @param  array  $additionalInfo
     * @param  string $parentTransactionId
     * @return null|Mage_Sales_Model_Order_Payment_Transaction
     */
    public function addTransaction($payment, $type, $transactionId, $additionalInfo, $parentTransactionId = null)
    {
        if (! $parentTransactionId) { // Not forcing parent transaction id.
            $txn = Mage::getModel('sales/order_payment_transaction')
                ->setOrderPaymentObject($payment)
                ->loadByTxnId($transactionId);

            if ($txn && $txn->getId() && $txn->getTxnType() !== $type) {
                $parentTransactionId = $txn->getTxnId();
            }
        }

        if ($parentTransactionId) {
            $payment->setParentTransactionId($parentTransactionId);
            $transactionId .= '-' . $type;
            $payment->setShouldCloseParentTransaction(true);
        }

        if ($type === Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
            $payment->setIsTransactionClosed(0);
        }

        if (method_exists($payment, 'addTransaction')) {
            $payment->setSkipTransactionCreation(false)
                ->setTransactionId($transactionId)
                ->setTransactionAdditionalInfo('raw_details_info', $additionalInfo);

            $payment->addTransaction($type, null, true);
        } else {
            // Set transaction parameters.
            $transaction = Mage::getModel('sales/order_payment_transaction')->setOrderPaymentObject($payment)
                ->setTxnType($type)
                ->setTxnId($transactionId)
                ->isFailsafe(true);

            if ($payment->hasIsTransactionClosed()) {
                $transaction->setIsClosed((int) $payment->getIsTransactionClosed());
            }

            // Set transaction addition information.
            $transaction->setAdditionalInformation('raw_details_info', $additionalInfo);

            // Link with sales entities.
            $payment->setLastTransId($transactionId);
            $payment->setCreatedTransaction($transaction);
            $payment->getOrder()->addRelatedObject($transaction);

            // Link with parent transaction.
            $parentTransactionId = $payment->getParentTransactionId();

            if ($parentTransactionId) {
                $transaction->setParentTxnId($parentTransactionId);
                if ($payment->getShouldCloseParentTransaction()) {
                    // Use getTransaction (that is public) instead of _lookupTransaction.
                    $parentTransaction = $payment->getTransaction($parentTransactionId);
                    if ($parentTransaction) {
                        $parentTransaction->isFailsafe(true)->close(false);
                        $payment->getOrder()->addRelatedObject($parentTransaction);
                    }
                }
            }
        }

        $txnExists = is_object($payment->getTransaction($transactionId));
        $msg = $txnExists ? 'Transaction %s was updated.' : 'Transaction %s was created.';
        $payment->getOrder()->addStatusHistoryComment($this->_getHelper()->__($msg, $transactionId));
    }

    /**
     * Get new order state and status according to the gateway response.
     *
     * @param  Lyranetwork_Payzen_Model_Api_Response|Lyranetwork_Payzen_Model_Api_Ws_ResultWrapper $response
     * @param  Mage_Sales_Model_Order $order
     * @param  boolean $ignoreFraud
     * @return Varien_Object
     */
    public function nextOrderState($response, $order, $ignoreFraud = false)
    {
        if ($response->isToValidatePayment()) {
            $newStatus = 'payzen_to_validate';
            $newState = $this->_getHelper()->getReviewState();
        } elseif ($response->isPendingPayment()) {
            $newStatus = 'payment_review';
            $newState = $this->_getHelper()->getReviewState();
        } else {
            if ($this->isSofort($response) || $this->isSepa($response)) {
                // Pending funds transfer order state.
                $newStatus = 'payzen_pending_transfer';
            } else {
                $newStatus = $this->_getHelper()->getCommonConfigData(
                    'registered_order_status',
                    $order->getStore()->getId()
                );
            }

            $processingStatuses = Mage::getModel('sales/order_config')
                ->getStateStatuses(Mage_Sales_Model_Order::STATE_PROCESSING, false);
            $newState = in_array($newStatus, $processingStatuses) ?
                Mage_Sales_Model_Order::STATE_PROCESSING : Mage_Sales_Model_Order::STATE_NEW;
        }

        $stateObject = new Varien_Object();

        if (! $ignoreFraud && $response->isSuspectedFraud()) {
            if (defined('Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW')) {
                $newState = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
            } else {
                $stateObject->setBeforeState($newState);
                $stateObject->setBeforeStatus($newStatus);

                // For magento 1.4.0.x.
                $newState = Mage_Sales_Model_Order::STATE_HOLDED;
            }

            $newStatus = 'fraud';
        }

        $stateObject->setState($newState);
        $stateObject->setStatus($newStatus);
        return $stateObject;
    }

    public function isSofort($response)
    {
        return $response->get('card_brand') === 'SOFORT_BANKING';
    }

    public function isSepa($response)
    {
        return $response->get('card_brand') === 'SDD';
    }

    /**
     * Convert gateway transaction statuses to magento transaction statuses
     *
     * @param  string $payzenType
     * @return string
     */
    public function convertTxnType($payzenType)
    {
        $type = false;

        $successStatuses = array_merge(
            Lyranetwork_Payzen_Model_Api_Api::getSuccessStatuses(),
            Lyranetwork_Payzen_Model_Api_Api::getPendingStatuses()
        );

        switch (true) {
            case in_array($payzenType, array('CAPTURED', 'ACCEPTED')):
                $type = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                break;

            case in_array($payzenType, $successStatuses):
                $type = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                break;

            case 'REFUSED':
            case 'EXPIRED':
            case 'CANCELLED':
            case 'NOT_CREATED':
            case 'ABANDONED':
                $type = Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID;
                break;

            default:
                $type = null; // Unknown.
                break;
        }

        return $type;
    }

    public function getNextInstallmentsTransStatus($firstStatus)
    {
        switch ($firstStatus) {
            case 'AUTHORISED_TO_VALIDATE':
                return 'WAITING_AUTHORISATION_TO_VALIDATE';

            case 'AUTHORISED':
                return 'WAITING_AUTHORISATION';

            default:
                return $firstStatus;
        }
    }

    protected function _getHelper()
    {
        return Mage::helper('payzen');
    }

    protected function _getRestHelper()
    {
        return Mage::helper('payzen/rest');
    }

    protected function _getOnepageForQuote($quote)
    {
        $onePage = Mage::getSingleton('checkout/type_onepage');
        $onePage->setQuote($quote);

        if ($quote->getCustomerId()) {
            $onePage->getCustomerSession()->loginById($quote->getCustomerId());
        }

        return $onePage;
    }

    private function _getPassword($isTest = true)
    {
        $standard = Mage::getModel('payzen/payment_standard');
        $crypted = $standard->getConfigData($isTest ? 'rest_private_key_test' : 'rest_private_key_prod');

        return Mage::helper('core')->decrypt($crypted);
    }
}
