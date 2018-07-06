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

class Lyra_Payzen_Helper_Payment extends Mage_Core_Helper_Abstract
{

    const IDENTIFIER = 'payzen_identifier'; // key to save if payment is by identifier
    const MULTI_OPTION = 'payzen_multi_option'; // key to save choosen multi option

    const RISK_CONTROL = 'payzen_risk_control'; // key to save risk control results
    const RISK_ASSESSMENT = 'payzen_risk_assessment'; // key to save risk assessment results
    const ALL_RESULTS = 'payzen_all_results'; // key to save risk assessment results
    const TRANS_UUID = 'payzen_trans_uuid';
    const BRAND_USER_CHOICE = 'payzen_brand_user_choice';

    const ONECLICK_LOCATION_CART = 'CART';
    const ONECLICK_LOCATION_PRODUCT = 'PRODUCT';
    const ONECLICK_LOCATION_BOTH = 'BOTH';

    public function doPaymentForm($controller)
    {
        // load order
        $lastIncrementId = $controller->getCheckout()->getLastRealOrderId();

        // check that there is an order to pay
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

        // check that there is products in cart
        if ($order->getTotalDue() == 0) {
            $this->_getHelper()->log(
                "Payment attempt with no amount. [Order = {$order->getId()}]" .
                " [IP = {$this->_getHelper()->getIpAddress()}]."
            );
            $controller->redirectBack('Order total is empty.');
            return;
        }

        // check that order is not processed yet
        if (! $controller->getCheckout()->getLastSuccessQuoteId()) {
            $this->_getHelper()->log(
                "Payment attempt with a quote already processed." .
                " [Order = {$order->getId()}] [IP = {$this->_getHelper()->getIpAddress()}]."
            );
            $controller->redirectBack('Order payment already processed.');
            return;
        }

        // add history comment and save it
        $order->addStatusHistoryComment(
            $this->_getHelper()->__('Client sent to PayZen gateway.'),
            false
        )->save();

        // clear quote data
        $controller->getCheckout()->setQuoteId(null);
        $controller->getCheckout()->setLastSuccessQuoteId(null);

        // inactivate quote
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(false)->save();
        }

        // redirect to gateway
        $this->_getHelper()->log("Display payment form and JavaScript for order #{$order->getId()}.");

        $controller->loadLayout();
        $controller->renderLayout();

        $this->_getHelper()->log(
            "Client {$order->getCustomerEmail()} sent to payment page for order #{$order->getId()}."
        );
    }

    public function doPaymentReturn($controller)
    {
        $request = $controller->getRequest()->getParams();

        // loading order
        $orderId = key_exists('vads_order_id', $request) ? $request['vads_order_id'] : 0;
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);

        // get store id from order
        $storeId = $order->getStore()->getId();

        // load API response
        $response = new Lyra_Payzen_Model_Api_Response(
            $request,
            $this->_getHelper()->getCommonConfigData('ctx_mode', $storeId),
            $this->_getHelper()->getCommonConfigData('key_test', $storeId),
            $this->_getHelper()->getCommonConfigData('key_prod', $storeId),
            $this->_getHelper()->getCommonConfigData('sign_algo', $storeId)
        );

        if (! $response->isAuthentified()) {
            // authentification failed
            $ip = $this->_getHelper()->getIpAddress();

            $this->_getHelper()->log(
                "{$ip} tries to access payzen/payment/return page without valid signature with parameters: " . print_r($request, true),
                Zend_Log::ERR
            );
            $this->_getHelper()->log(
                'Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.'
            );

            $controller->redirectError($order);
            return;
        }

        if (! $orderId) {
            $this->_getHelper()->log(
                "Order ID not returned. Payment result : {$response->getLogMessage()}",
                Zend_Log::ERR
            );
            $controller->redirectError($order);
            return;
        }

        $this->_getHelper()->log("Request authenticated for order #{$order->getId()}.");

        if ($order->getStatus() == 'pending_payment') {
            // order waiting for payment
            $this->_getHelper()->log("Order #{$order->getId()} is waiting for payment.");

            if ($this->_isPaymentSuccessfullyProcessed($response)) {
                $this->_getHelper()->log(
                    "Payment for order #{$order->getId()} has been confirmed by client return ! " .
                    " This means the notification URL did not work.",
                    Zend_Log::WARN
                );

                // save order and optionally create invoice
                $this->_registerOrder($order, $response);

                // display success page
                $controller->redirectResponse($order, true /* is success ? */, true /* IPN URL warn in TEST mode */);
            } else {
                $this->_getHelper()->log("Payment for order #{$order->getId()} has failed.");

                // cancel order
                $this->_cancelOrder($order, $response);

                // redirect to cart page
                $controller->redirectResponse($order, false /* is success ? */);
            }
        } else {
            // payment already processed
            $this->_getHelper()->log("Order #{$order->getId()} has already been processed.");

            $acceptedStatus = $this->_getHelper()->getCommonConfigData('registered_order_status', $storeId);
            $successStatuses = array(
                $acceptedStatus,
                'complete' /* case of virtual orders */,
                'payment_review' /* case of Oney (and other) pending payments */,
                'fraud' /* fraud status is taken as successful because it's just a suspicion */,
                'payzen_to_validate' /* payment will be done after manual validation */
            );

            if ($this->_isPaymentSuccessfullyProcessed($response) && in_array($order->getStatus(), $successStatuses)) {
                $this->_getHelper()->log("Order #{$order->getId()} is confirmed.");
                $controller->redirectResponse($order, true /* is success ? */);
            } elseif ($order->isCanceled() && ! $this->_isPaymentSuccessfullyProcessed($response)) {
                $this->_getHelper()->log("Order #{$order->getId()} cancelation is confirmed.");
                $controller->redirectResponse($order, false /* is success ? */);
            } else {
                // this is an error case, the client returns with an error but the payment already has been accepted
                $this->_getHelper()->log(
                    "Order #{$order->getId()} has been validated but we receive a payment error code ! ",
                    Zend_Log::ERR
                );
                $controller->redirectError($order);
            }
        }
    }

    public function doPaymentCheck($controller)
    {
        $post = $controller->getRequest()->getPost();

        // loading order
        $orderId = key_exists('vads_order_id', $post) ? $post['vads_order_id'] : 0;
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);

        // get store id from order
        $storeId = $order->getStore()->getId();

        // init app with correct store id
        Mage::app()->init($storeId, 'store');

        // load API response
        $response = new Lyra_Payzen_Model_Api_Response(
            $post,
            $this->_getHelper()->getCommonConfigData('ctx_mode', $storeId),
            $this->_getHelper()->getCommonConfigData('key_test', $storeId),
            $this->_getHelper()->getCommonConfigData('key_prod', $storeId),
            $this->_getHelper()->getCommonConfigData('sign_algo', $storeId)
        );

        if (! $response->isAuthentified()) {
            $ip = $this->_getHelper()->getIpAddress();

            // authentification failed
            $this->_getHelper()->log(
                "{$ip} tries to access payzen/payment/check page without valid signature with parameters: " . print_r($post, true),
                Zend_Log::ERR
            );
            $this->_getHelper()->log(
                'Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.'
            );

            $controller->getResponse()->setBody($response->getOutputForPlatform('auth_fail'));
            return;
        }

        $this->_getHelper()->log("Request authenticated for order #{$order->getId()}.");

        $reviewStatuses = array('payment_review', 'payzen_to_validate', 'fraud');
        if ($order->getStatus() == 'pending_payment' || in_array($order->getStatus(), $reviewStatuses)) {
            // order waiting for payment
            $this->_getHelper()->log("Order #{$order->getId()} is waiting for payment.");

            if ($this->_isPaymentSuccessfullyProcessed($response)) {
                $this->_getHelper()->log(
                    "Payment for order #{$order->getId()} has been confirmed by notification URL."
                );

                $stateObject = $this->nextOrderState($response, $order);
                if ($order->getStatus() == $stateObject->getStatus()) { // payment status is unchanged
                    // display notification url confirmation message
                    $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ok_already_done'));
                } else {
                    // save order and optionally create invoice
                    $this->_registerOrder($order, $response);

                    // display notification url confirmation message
                    $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ok'));
                }
            } else {
                $this->_getHelper()->log(
                    "Payment for order #{$order->getId()} has been invalidated by notification URL."
                );

                // cancel order
                $this->_cancelOrder($order, $response);

                // display notification url failure message
                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ko'));
            }
        } else {
            // payment already processed

            $acceptedStatus = $this->_getHelper()->getCommonConfigData('registered_order_status', $storeId);
            $successStatuses = array(
                $acceptedStatus,
                'complete' /* case of virtual orders */
            );

            if ($this->_isPaymentSuccessfullyProcessed($response) && in_array($order->getStatus(), $successStatuses)) {
                $this->_getHelper()->log("Order #{$order->getId()} is confirmed.");

                if ($response->get('operation_type') == 'CREDIT') {
                    // this is a refund TODO create credit memo

                    $expiry = '';
                    if ($response->get('expiry_month') && $response->get('expiry_year')) {
                        $expiry = str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT)
                            . ' / ' . $response->get('expiry_year');
                    }

                    $transactionId = $response->get('trans_id') . '-' . $response->get('sequence_number');

                    // save paid amount
                    $currency = Lyra_Payzen_Model_Api_Api::findCurrencyByNumCode($response->get('currency'));
                    $amount = number_format($currency->convertAmountToFloat($response->get('amount')), $currency->getDecimals(), ',', ' ');

                    $amountDetail = $amount . ' ' . $currency->getAlpha3();

                    if ($response->get('effective_currency') && ($response->get('currency') !== $response->get('effective_currency'))) {
                        $effectiveCurrency = Lyra_Payzen_Model_Api_Api::findCurrencyByNumCode($response->get('effective_currency'));

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
                        'Transaction Status' => $response->getTransStatus(),
                        'Means of Payment' => $response->get('card_brand'),
                        'Credit Card Number' => $response->get('card_number'),
                        'Expiration Date' => $expiry
                    );

                    $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND;

                    $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
                } else {
                    // update transaction info
                    $this->updatePaymentInfo($order, $response);
                }

                $order->save();

                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ok_already_done'));
            } elseif ($order->isCanceled() && ! $this->_isPaymentSuccessfullyProcessed($response)) {
                $this->_getHelper()->log("Order #{$order->getId()} cancelation is confirmed.");
                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ko_already_done'));
            } else {
                // this is an error case, the client returns with an error but the payment already has been accepted
                $this->_getHelper()->log(
                    "Order #{$order->getId()} has been validated but we receive a payment error code ! ",
                    Zend_Log::ERR
                );
                $controller->getResponse()->setBody($response->getOutputForPlatform('payment_ko_on_order_ok'));
            }
        }
    }

    private function _isPaymentSuccessfullyProcessed(Lyra_Payzen_Model_Api_Response $response)
    {
        if ($response->isAcceptedPayment()) {
            return true;
        }

        return ! $response->getTransStatus() && $response->get('identifier') &&
            in_array($response->get('identifier_status'), array('CREATED', 'UPDATED'));
    }

    /**
     * Update order status and eventually create invoice.
     *
     * @param Mage_Sales_Model_Order $order
     * @param Lyra_Payzen_Model_Api_Response $response
     */
    protected function _registerOrder(Mage_Sales_Model_Order $order, Lyra_Payzen_Model_Api_Response $response)
    {
        $this->_getHelper()->log("Saving payment for order #{$order->getId()}.");

        // update authorized amount
        $order->getPayment()->setAmountAuthorized($order->getTotalDue());
        $order->getPayment()->setBaseAmountAuthorized($order->getBaseTotalDue());

        // retrieve new order state and status
        $stateObject = $this->nextOrderState($response, $order);
        $this->_getHelper()->log(
            "Order #{$order->getId()}, new state : {$stateObject->getState()}"
            . ", new status : {$stateObject->getStatus()}."
        );

        $order->setState($stateObject->getState(), $stateObject->getStatus(), $response->getMessage());
        if ($stateObject->getState() == Mage_Sales_Model_Order::STATE_HOLDED) { // for Magento 1.4.0.x
            $order->setHoldBeforeState($stateObject->getBeforeState());
            $order->setHoldBeforeStatus($stateObject->getBeforeStatus());
        }

        // save gateway responses
        $this->updatePaymentInfo($order, $response);

        // try to save PayZen identifier if any
        $this->_saveIdentifier($order, $response);

        // try to create invoice
        $this->createInvoice($order);

        $this->_getHelper()->log("Saving confirmed order #{$order->getId()} and sending e-mail if not disabled.");
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
     * @param Lyra_Payzen_Model_Api_Response|Lyra_Payzen_Model_Api_Ws_ResultWrapper $response
     */
    public function updatePaymentInfo(Mage_Sales_Model_Order $order, $response)
    {
        // set common payment information
        $order->getPayment()->setCcTransId($response->get('trans_id'))
                            ->setCcType($response->get('card_brand'))
                            ->setCcStatus($response->getResult())
                            ->setCcStatusDescription($response->getMessage())
                            ->setAdditionalInformation(self::ALL_RESULTS, serialize($response->getAllResults()))
                            ->setAdditionalInformation(self::TRANS_UUID, $response->get('trans_uuid'));

        if ($response->isCancelledPayment()) {
            // no more data to save
            return;
        }

        if ($response->get('brand_management')) {
            $brandInfo = Mage::helper('core')->jsonDecode($response->get('brand_management'), Zend_Json::TYPE_OBJECT);

            $userChoice = (isset($brandInfo->userChoice) && $brandInfo->userChoice);
            $order->getPayment()->setAdditionalInformation(self::BRAND_USER_CHOICE, $userChoice);
        }

        // save risk control result if any
        $riskControl = $response->getRiskControl();
        if (! empty($riskControl)) {
            $order->getPayment()->setAdditionalInformation(self::RISK_CONTROL, $riskControl);
        }

        // save risk assessment result if any
        $riskAssessment = $response->getRiskAssessment();
        if (! empty($riskAssessment)) {
            $order->getPayment()->setAdditionalInformation(self::RISK_ASSESSMENT, $riskAssessment);
        }

        // set is_fraud_detected flag
        $order->getPayment()->setIsFraudDetected($response->isSuspectedFraud());

        $currency = Lyra_Payzen_Model_Api_Api::findCurrencyByNumCode($response->get('currency'));

        if ($response->get('card_brand') == 'MULTI') { // multi brand
            $data = Mage::helper('core')->jsonDecode($response->get('payment_seq'), Zend_Json::TYPE_OBJECT);
            $transactions = $data->{'transactions'};

            // save transaction details to sales_payment_transaction
            foreach ($transactions as $trs) {
                // save transaction details to sales_payment_transaction
                $expiry = '';
                if (! empty($trs->{'expiry_month'}) && ! empty($trs->{'expiry_year'})) {
                    $expiry = str_pad($trs->{'expiry_month'}, 2, '0', STR_PAD_LEFT) . ' / ' . $trs->{'expiry_year'};
                }

                $transactionId = $response->get('trans_id') . '-' . $trs->{'sequence_number'};

                // save paid amount
                $amount = number_format($currency->convertAmountToFloat($trs->{'amount'}), $currency->getDecimals(), ',', ' ');
                $amountDetail = $amount . ' ' . $currency->getAlpha3();

                $additionalInfo = array(
                    'Transaction Type' => $trs->{'operation_type'},
                    'Amount' => $amountDetail,
                    'Transaction ID' => $transactionId,
                    'Extra Transaction ID' => property_exists($trs, 'ext_trans_id')
                        && isset($trs->{'ext_trans_id'}) ? $trs->{'ext_trans_id'} : '',
                    'Transaction Status' => $trs->{'trans_status'},
                    'Means of Payment' => $trs->{'card_brand'},
                    'Credit Card Number' => $trs->{'card_number'},
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
            if ($response->get('threeds_status') === 'Y') {
                $threedsCavv = $response->get('threeds_cavv');
            }

            // save payment infos to sales_flat_order_payment
            $order->getPayment()->setCcExpMonth($response->get('expiry_month'))
                                ->setCcExpYear($response->get('expiry_year'))
                                ->setCcNumberEnc($response->get('card_number'))
                                ->setCcSecureVerify($threedsCavv);

            // format card expiration data
            $expiry = '';
            if ($response->get('expiry_month') && $response->get('expiry_year')) {
                $expiry = str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT) . ' / '
                    . $response->get('expiry_year');
            }

            // Magento transaction type
            $transactionType = $this->convertTxnType($response->getTransStatus());

            // total payment amount and presentation date
            $totalAmount = $response->get('amount');
            $firstDate = strtotime($response->get('presentation_date') . ' UTC');

            $option = @unserialize($order->getPayment()->getAdditionalData()); // get choosen payment option if any
            if ($response->get('sequence_number') == 1 && (stripos($order->getPayment()->getMethod(), 'payzen_multi') === 0)
                && is_array($option) && ! empty($option)) {
                // first payment of payment in installments
                if (isset($option['first']) && $option['first']) {
                    $firstAmount = round($totalAmount * $option['first'] / 100);
                } else {
                    $firstAmount = round($totalAmount / $count);
                }

                // double cast to avoid rounding
                $installmentAmount = (int) (string) (($totalAmount - $firstAmount) / ((int) $option['count'] - 1));

                for ($i = 1; $i <= (int) $option['count']; $i++) {
                    $transactionId = $response->get('trans_id') . '-' . $i;

                    $delay = (int) $option['period'] * ($i - 1);
                    $date = strtotime("+$delay days", $firstDate);

                    switch(true) {
                        case($i == 1): // first transaction
                            $amount = $firstAmount;
                            break;

                        case($i == $option['count']): // last transaction
                            $amount = $totalAmount - $firstAmount - $installmentAmount * ($i - 2);
                            break;

                        default: // others
                            $amount = $installmentAmount;
                            break;
                    }

                    $floatAmount = number_format($currency->convertAmountToFloat($amount), $currency->getDecimals(), ',', ' ');
                    $amountDetail = $floatAmount . ' ' . $currency->getAlpha3();

                    if (($rate = $response->get('change_rate')) && $response->get('effective_currency') &&
                        ($response->get('currency') !== $response->get('effective_currency'))) {
                        // effective amount
                        $effectiveCurrency = Lyra_Payzen_Model_Api_Api::findCurrencyByNumCode($response->get('effective_currency'));

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
                        'Transaction Status' => ($i == 1) ? $response->getTransStatus()
                            : $this->getNextInstallmentsTransStatus($response->getTransStatus()),
                        'Means of Payment' => $response->get('card_brand'),
                        'Credit Card Number' => $response->get('card_number'),
                        'Expiration Date' => $expiry,
                        '3DS Authentication' => $threedsCavv
                    );

                    if ($transactionType) {
                        $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
                    }
                }
            } else {
                // save transaction details to sales_payment_transaction
                $transactionId = $response->get('trans_id') . '-' . $response->get('sequence_number');

                $amount = number_format($currency->convertAmountToFloat($totalAmount), $currency->getDecimals(), ',', ' ');
                $amountDetail = $amount . ' ' . $currency->getAlpha3();

                if ($response->get('effective_currency') && ($response->get('currency') !== $response->get('effective_currency'))) {
                    // effective amount
                    $effectiveCurrency = Lyra_Payzen_Model_Api_Api::findCurrencyByNumCode($response->get('effective_currency'));

                    $effectiveAmount = number_format(
                        $effectiveCurrency->convertAmountToFloat($response->get('effective_amount')),
                        $effectiveCurrency->getDecimals(),
                        ',',
                        ' '
                    );

                    $amountDetail = $effectiveAmount. ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
                }

                $additionalInfo = array(
                    'Transaction Type' => $response->get('operation_type'),
                    'Amount' => $amountDetail,
                    'Presentation Date' => Mage::helper('core')->formatDate(date('Y-m-d', $firstDate)),
                    'Transaction ID' => $transactionId,
                    'Transaction Status' => $response->getTransStatus(),
                    'Means of Payment' => $response->get('card_brand'),
                    'Credit Card Number' => $response->get('card_number'),
                    'Expiration Date' => $expiry,
                    '3DS Authentication' => $threedsCavv
                );

                if ($transactionType) {
                    $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
                }
            }
        }

        // skip automatic transaction creation
        $order->getPayment()->setTransactionId(null)->setSkipTransactionCreation(true);
    }

    protected function _saveIdentifier(Mage_Sales_Model_Order $order, Lyra_Payzen_Model_Api_Response $response)
    {
        if (! $order->getCustomerId()) {
            return;
        }

        if ($response->get('identifier') && (
            $response->get('identifier_status') == 'CREATED' /* page_action is REGISTER_PAY or ASK_REGISTER_PAY */ ||
            $response->get('identifier_status') == 'UPDATED' /* page_action is REGISTER_UPDATE_PAY */
        )) {
            $customer = Mage::getModel('customer/customer');
            $customer->load($order->getCustomerId());

            $this->_getHelper()->log(
                "Identifier for customer #{$customer->getId()} successfully "
                . "created or updated on payment gateway. Let's save it to customer entity."
            );

            $customer->setData('payzen_identifier', $response->get('identifier'));

            // mask all card digits unless the last 4 ones
            $number = $response->get('card_number');
            $masked = '';

            $matches = array();
            if (preg_match('#^([A-Z]{2}[0-9]{2}[A-Z0-9]{10,30})(_[A-Z0-9]{8,11})?$#i', $number, $matches)) {
                // IBAN(_BIC)
                $masked .= isset($matches[2]) ? str_replace('_', '', $matches[2]) . ' / ' : ''; // BIC

                $iban = $matches[1];
                $masked .= substr($iban, 0, 4) . str_repeat('X', strlen($iban) - 8) . substr($iban, -4);
            } elseif (strlen($number) > 4) {
                $masked = str_repeat('X', strlen($number) - 4) . substr($number, -4);

                if ($response->get('expiry_month') && $response->get('expiry_year')) {
                    // format card expiration data
                    $masked .= ' ';
                    $masked .= str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT);
                    $masked .= ' / ';
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

    public function createInvoice(Mage_Sales_Model_Order $order)
    {
        // flag that is true if automatically create invoice
        $autoCapture = $this->_getHelper()->getCommonConfigData('capture_auto', $order->getStore()->getId());

        if (! $autoCapture || ($order->getState() != 'processing') || ! $order->canInvoice()) {
            // creating invoice not allowed
            return;
        }

        $this->_getHelper()->log("Creating invoice for order #{$order->getId()}.");

        // convert order to invoice
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
        $invoice->setTransactionId($order->getPayment()->getLastTransId());
        $invoice->register()->save();
        $order->addRelatedObject($invoice);

        // add history entry
        $message = $this->_getHelper()->__('Invoice %s was created.', $invoice->getIncrementId());
        $order->addStatusHistoryComment($message);
    }

    /**
     * Cancel order.
     *
     * @param Mage_Sales_Model_Order $order
     * @param Lyra_Payzen_Model_Api_Response $response
     */
    protected function _cancelOrder(Mage_Sales_Model_Order $order, Lyra_Payzen_Model_Api_Response $response)
    {
        $this->_getHelper()->log("Canceling order #{$order->getId()}.");

        $order->registerCancellation($response->getMessage());

        // save gateway responses
        $this->updatePaymentInfo($order, $response);
        $order->save();

        Mage::dispatchEvent('order_cancel_after', array('order' => $order));
    }

    /**
     * Public access to Mage_Sales_Model_Order_Payment::_addTransaction method to let it available for magento 1.4.0.1.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param string $type
     * @param string $transactionId
     * @param array $additionalInfo
     * @param string $parentTransactionId
     * @return null|Mage_Sales_Model_Order_Payment_Transaction
     */
    public function addTransaction($payment, $type, $transactionId, $additionalInfo, $parentTransactionId = null)
    {
        if (! $parentTransactionId) { // not forcing parent transaction id
            $txn = Mage::getModel('sales/order_payment_transaction')
                    ->setOrderPaymentObject($payment)
                    ->loadByTxnId($transactionId);

            if ($txn && $txn->getId() && $txn->getTxnType() != $type) {
                $parentTransactionId = $txn->getTxnId();
            }
        }

        if ($parentTransactionId) {
            $payment->setParentTransactionId($parentTransactionId);
            $transactionId .= '-' . $type;
            $payment->setShouldCloseParentTransaction(true);
        }

        if ($type == Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
            $payment->setIsTransactionClosed(0);
        }

        if (method_exists($payment, 'addTransaction')) {
            $payment->setSkipTransactionCreation(false)
                    ->setTransactionId($transactionId)
                    ->setTransactionAdditionalInfo('raw_details_info', $additionalInfo);

            $payment->addTransaction($type, null, true);
        } else {
            // set transaction parameters
            $transaction = Mage::getModel('sales/order_payment_transaction')->setOrderPaymentObject($payment)
                                                                            ->setTxnType($type)
                                                                            ->setTxnId($transactionId)
                                                                            ->isFailsafe(true);

            if ($payment->hasIsTransactionClosed()) {
                $transaction->setIsClosed((int) $payment->getIsTransactionClosed());
            }

            //set transaction addition information
            $transaction->setAdditionalInformation('raw_details_info', $additionalInfo);

            // link with sales entities
            $payment->setLastTransId($transactionId);
            $payment->setCreatedTransaction($transaction);
            $payment->getOrder()->addRelatedObject($transaction);

            // link with parent transaction
            $parentTransactionId = $payment->getParentTransactionId();

            if ($parentTransactionId) {
                $transaction->setParentTxnId($parentTransactionId);
                if ($payment->getShouldCloseParentTransaction()) {
                    // use getTransaction (that is public) instead of _lookupTransaction
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
     * Get new order state and status according to PayZen response.
     *
     * @param Lyra_Payzen_Model_Api_Response|Lyra_Payzen_Model_Api_Ws_ResultWrapper $response
     * @param Mage_Sales_Model_Order $order
     * @param boolean $ignoreFraud
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
                // pending funds transfer order state
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

                // for Magento 1.4.0.x
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
        return $response->get('card_brand') == 'SOFORT_BANKING';
    }

    public function isSepa($response)
    {
        return $response->get('card_brand') == 'SDD';
    }

    /**
     * Convert PayZen transaction statuses to magento transaction statuses
     *
     * @param string $payzenType
     * @return string
     */
    public function convertTxnType($payzenType)
    {
        $type = false;

        switch ($payzenType) {
            case 'UNDER_VERIFICATION':
            case 'INITIAL':
            case 'WAITING_AUTHORISATION_TO_VALIDATE':
            case 'WAITING_AUTHORISATION':
            case 'AUTHORISED_TO_VALIDATE':
            case 'AUTHORISED':
            case 'CAPTURE_FAILED':
                $type = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                break;

            case 'REFUSED':
            case 'EXPIRED':
            case 'CANCELLED':
            case 'NOT_CREATED':
            case 'ABANDONED':
                $type = Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID;
                break;

            case 'CAPTURED':
                $type = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                break;

            default:
                $type = null; // unknown
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
}
