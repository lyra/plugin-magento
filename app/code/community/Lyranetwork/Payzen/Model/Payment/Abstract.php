<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

abstract class Lyranetwork_Payzen_Model_Payment_Abstract extends Mage_Payment_Model_Method_Abstract
{
    protected $_infoBlockType = 'payzen/info';

    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseForMultishipping = false;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_isInitializeNeeded = true;
    protected $_canSaveCc = false;
    protected $_canReviewPayment = true;

    protected $_payzenRequest = null;

    protected $_currencies = array();
    protected $currentCurrencyCode = null;
    protected $needsCartData = false;

    public function __construct()
    {
        parent::__construct();

        $this->_payzenRequest = new Lyranetwork_Payzen_Model_Api_Request();
    }

    /**
     * @param  Mage_Sales_Model_Order $order
     *
     * @return <string:mixed> array of params as key=>value
     */
    public function getFormFields($order)
    {
        // Set order_id.
        $this->_payzenRequest->set('order_id', $order->getIncrementId());

        // Amount in current order currency.
        $amount = $order->getGrandTotal();

        // Set currency.
        $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($order->getOrderCurrencyCode());
        if ($currency == null) {
            // If currency is not supported, use base currency.
            $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($order->getBaseCurrencyCode());

            // ... and order total in base currency
            $amount = $order->getBaseGrandTotal();
        }

        $this->_payzenRequest->set('currency', $currency->getNum());

        // Set the amount to pay.
        $this->_payzenRequest->set('amount', $currency->convertAmountToInteger($amount));

        $contrib = $this->_getHelper()->getCommonConfigData('cms_identifier') . '_' .
            $this->_getHelper()->getCommonConfigData('plugin_version') . '/';
        $this->_payzenRequest->set('contrib', $contrib . Mage::getVersion() . '/' . PHP_VERSION);

        // Set config parameters.
        $configFields = array('site_id', 'key_test', 'key_prod', 'ctx_mode', 'capture_delay', 'validation_mode',
            'theme_config', 'shop_name', 'shop_url', 'redirect_enabled', 'redirect_success_timeout',
            'redirect_success_message', 'redirect_error_timeout', 'redirect_error_message', 'return_mode',
            'sign_algo'
        );
        foreach ($configFields as $field) {
            $this->_payzenRequest->set($field, $this->_getHelper()->getCommonConfigData($field));
        }

        // Check if capture_delay and validation_mode are overriden in submodules.
        if (is_numeric($this->getConfigData('capture_delay'))) {
            $this->_payzenRequest->set('capture_delay', $this->getConfigData('capture_delay'));
        }

        if ($this->getConfigData('validation_mode') !== '-1') {
            $this->_payzenRequest->set('validation_mode', $this->getConfigData('validation_mode'));
        }

        // Set return url (build it and add store_id).
        $admin = $this->_getHelper()->isAdmin();
        $storeId = $admin ? 0 : $order->getStore()->getId();
        $path = $admin ? 'adminhtml/payzen_payment/return' : 'payzen/payment/return';
        $returnUrl = $this->_getHelper()->prepareUrl($path, $storeId, $admin);

        $this->_getHelper()->log('The complete return URL is ' . $returnUrl);
        $this->_payzenRequest->set('url_return', $returnUrl);

        // Set the language code.
        $lang = strtolower(substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        if (! Lyranetwork_Payzen_Model_Api_Api::isSupportedLanguage($lang)) {
            $lang = $this->_getHelper()->getCommonConfigData('language');
        }

        $this->_payzenRequest->set('language', $lang);

        // Available_languages is given as csv by magento.
        $availableLanguages = explode(',', $this->_getHelper()->getCommonConfigData('available_languages'));
        $availableLanguages = in_array('', $availableLanguages) ? '' : implode(';', $availableLanguages);
        $this->_payzenRequest->set('available_languages', $availableLanguages);

        // Activate 3DS?
        $threedsMpi = null;
        $configOptions = unserialize($this->_getHelper()->getCommonConfigData('custgroup_threeds_min_amount'));
        if (is_array($configOptions) && ! empty($configOptions)) {
            $group = $order->getCustomerGroupId();

            $allThreedsMinAmount = null;
            $threedsMinAmount = null;
            $flag = 0;
            foreach ($configOptions as $value) {
                if (empty($value)) {
                    continue;
                }

                if ($value['code'] === 'all') {
                    $allThreedsMinAmount = $value['amount_min'];
                    $flag++;
                } elseif ($value['code'] === $group) {
                    $threedsMinAmount = $value['amount_min'];
                    $flag++;
                }

                if ($flag === 2) { // Both allThreedsMinAmount and threedsMinAmount are initialized.
                    break;
                }
            }

            if (! $threedsMinAmount) {
                $threedsMinAmount = $allThreedsMinAmount;
            }

            if ($threedsMinAmount && ($order->getTotalDue() < $threedsMinAmount)) {
                $threedsMpi = '2';
            }
        }

        $this->_payzenRequest->set('threeds_mpi', $threedsMpi);

        $this->_payzenRequest->set('cust_email', $order->getCustomerEmail());
        $this->_payzenRequest->set('cust_id', $order->getCustomerId());
        $this->_payzenRequest->set('cust_title', $order->getBillingAddress()->getPrefix() ? $order->getBillingAddress()->getPrefix() : null);
        $this->_payzenRequest->set('cust_first_name', $order->getBillingAddress()->getFirstname());
        $this->_payzenRequest->set('cust_last_name', $order->getBillingAddress()->getLastname());
        $this->_payzenRequest->set('cust_address', $order->getBillingAddress()->getStreet(1) . ' ' . $order->getBillingAddress()->getStreet(2));
        $this->_payzenRequest->set('cust_zip', $order->getBillingAddress()->getPostcode());
        $this->_payzenRequest->set('cust_city', $order->getBillingAddress()->getCity());
        $this->_payzenRequest->set('cust_state', !is_numeric($order->getBillingAddress()->getRegionCode()) ?
            $order->getBillingAddress()->getRegionCode() : $order->getBillingAddress()->getRegion());
        $this->_payzenRequest->set('cust_country', $order->getBillingAddress()->getCountryId());
        $this->_payzenRequest->set('cust_phone', $order->getBillingAddress()->getTelephone());
        $this->_payzenRequest->set('cust_cell_phone', $order->getBillingAddress()->getTelephone());

        $address = $order->getShippingAddress();
        if (is_object($address)) { // Shipping is supported.
            $this->_payzenRequest->set('ship_to_first_name', $address->getFirstname());
            $this->_payzenRequest->set('ship_to_last_name', $address->getLastname());
            $this->_payzenRequest->set('ship_to_city', $address->getCity());
            $this->_payzenRequest->set('ship_to_street', $address->getStreet(1));
            $this->_payzenRequest->set('ship_to_street2', $address->getStreet(2));
            $this->_payzenRequest->set('ship_to_state', !is_numeric($address->getRegionCode()) ?
                $address->getRegionCode() : $address->getRegion());
            $this->_payzenRequest->set('ship_to_country', $address->getCountryId());
            $this->_payzenRequest->set('ship_to_phone_num', $address->getTelephone());
            $this->_payzenRequest->set('ship_to_zip', $address->getPostcode());
        }

        if ($admin) {
            $session = Mage::getSingleton('adminhtml/session_quote');
            $sendEmail = $session->getPayzenCanSendNewEmail(true);
            $this->_payzenRequest->addExtInfo('send_confirmation', $sendEmail);
        }

        // Set method-specific parameters.
        $this->_setExtraFields($order);

        // add cart data
        if ($this->_getHelper()->getCommonConfigData('send_cart_detail') || $this->needsCartData || $this->_proposeOney()) {
            Mage::helper('payzen/util')->setCartData($order, $this->_payzenRequest, $this->needsCartData || $this->_proposeOney());
        }

        // Set other data specific to FacilyPay Oney payment ond risk assessment module.
        Mage::helper('payzen/util')->setAdditionalShippingData($order, $this->_payzenRequest, $this->_proposeOney(), $this->_isNewOneyApi());

        $paramsToLog = $this->_payzenRequest->getRequestFieldsArray(true);
        $this->_getHelper()->log('Payment parameters: ' . print_r($paramsToLog, true));

        return $this->_payzenRequest->getRequestFieldsArray();
    }

    abstract protected function _setExtraFields($order);

    protected function _proposeOney()
    {
        return false;
    }

    protected function _isNewOneyApi()
    {
        return false;
    }

    /**
     * Retrieve information from payment configuration.
     *
     * @param string                                $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if (is_null($storeId) && ! $this->getStore() && $this->_getHelper()->isAdmin()) {
            $storeId = Mage::getSingleton('adminhtml/session_quote')->getStoreId();
        }

        return parent::getConfigData($field, $storeId);
    }

    /**
     * Return the payment gateway URL.
     *
     * @return string
     */
    public function getPlatformUrl()
    {
        return $this->_getHelper()->getCommonConfigData('platform_url');
    }

    /**
     * The URL the customer is redirected to after clicking on "Confirm order".
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('payzen/payment/form', array('_secure' => true));
    }

    /**
     * Attempt to accept a pending payment.
     *
     * @param  Mage_Sales_Model_Order_Payment $payment
     * @return bool
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        parent::acceptPayment($payment);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        // Client did not configure private key in module backend, let Magento offline accept payment.
        if (! $this->_getRestHelper()->getPassword($storeId)) {
            $this->_getHelper()->log("Cannot get online payment information for order #{$order->getIncrementId()}: private key is not configured, let Magento accepts payment.");
            return true;
        }

        $this->_getHelper()->log("Get payment information online for order #{$order->getIncrementId()}.");

        try {
            // Retrieve transaction UUID.
            $uuid = $payment->getAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::TRANS_UUID);
            if (! $uuid) { // Retro compatibility.
                $data = $this->_getPaymentDetails($order, false);
                $getPaymentDetails['answer'] = reset($data);
                $getPaymentDetails['status'] = 'SUCCESS';
            } else {
                $requestData = array('uuid' => $uuid);

                // Perform our request.
                $client = new Lyranetwork_Payzen_Model_Api_Rest(
                    $this->_getHelper()->getCommonConfigData('rest_url', $storeId),
                    $this->_getHelper()->getCommonConfigData('site_id', $storeId),
                    $this->_getRestHelper()->getPassword($storeId)
                );

                $getPaymentDetails = $client->post('V4/Transaction/Get', json_encode($requestData));
            }

            $successStatuses = array_merge(
                Lyranetwork_Payzen_Model_Api_Api::getSuccessStatuses(),
                Lyranetwork_Payzen_Model_Api_Api::getPendingStatuses()
            );

            $this->_getRestHelper()->checkResult($getPaymentDetails, $successStatuses);

            // Check operation type.
            $transType = $getPaymentDetails['answer']['operationType'];
            if ($transType !== 'DEBIT') {
                throw new \Exception("Unexpected transaction type returned ($transType).");
            }

            $this->_getHelper()->log("Updating payment information for accepted order #{$order->getIncrementId()}.");

            // Payment is accepted by merchant.
            $payment->setIsFraudDetected(false);

            // Wrap payment result to use traditional order creation tunnel.
            $data = $this->_getRestHelper()->convertRestResult($getPaymentDetails['answer'], true);

            // Load API response.
            $response = new Lyranetwork_Payzen_Model_Api_Response($data, null, null, null);

            $stateObject = $this->_getPaymentHelper()->nextOrderState($response, $order, true);

            $this->_getHelper()->log("Order #{$order->getIncrementId()}, new state : {$stateObject->getState()}, new status : {$stateObject->getStatus()}.");
            $order->setState($stateObject->getState(), $stateObject->getStatus(), $this->_getHelper()->__('The payment has been accepted.'));

            // Try to create invoice.
            $this->_getPaymentHelper()->createInvoice($order);

            $order->save();
            $this->_getAdminSession()->addSuccess($this->_getHelper()->__('The payment has been accepted.'));

            $redirectUrl = Mage::getUrl('*/sales_order/view', array('order_id' => $order->getId()));
            Mage::app()->getResponse()->setRedirect($redirectUrl)->sendHeadersAndExit();
        } catch(UnexpectedValueException $e) {
            $this->_getHelper()->log("getPaymentDetails error with code {$e->getCode()}: {$e->getMessage()}.", Zend_Log::ERR);

            Mage::throwException($e->getMessage());
         } catch (Exception $e) {
            $this->_getHelper()->log("Exception with code {$e->getCode()}: {$e->getMessage()}", Zend_Log::ERR);

            if ($e->getCode() <= -1) { // Manage cUrl errors.
                $message = __("Please consult the PayZen logs for more details.");
            } else {
                $message = $e->getMessage();
            }

            $this->_getAdminSession()->addError($message);

            throw $e;
         }
    }

    /**
     * Attempt to deny a pending payment.
     *
     * @param  Mage_Sales_Model_Order_Payment $payment
     * @return bool
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        parent::denyPayment($payment);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        // Client did not configure private key in module backend, let Magento offline cancel payment.
        if (! $this->_getRestHelper()->getPassword($storeId)) {
            $this->_getHelper()->log("Cannot online cancel payment for order #{$order->getIncrementId()}: private key is not configured, let Magento cancel payment.");
            $this->_getAdminSession()->addWarning(__('Payment is cancelled only in Magento. Please, consider cancelling the payment in PayZen Back Office.'));
            return true;
        }

        $this->_getHelper()->log("Cancel payment online for order #{$order->getIncrementId()}.");

        try {
            // Retrieve transaction UUID.
            $uuid = $payment->getAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::TRANS_UUID);
            if (! $uuid) { // Retro compatibility.
                // Get UUID from Order.
                $uuidArray = $this->_getPaymentDetails($order);
                $uuid = reset($uuidArray);
            }

            $requestData = array(
                'uuid' => $uuid,
                'resolutionMode' => 'CANCELLATION_ONLY',
                'comment' => $this->_getUserInfo()
            );

            // Load API response.
            $client = new Lyranetwork_Payzen_Model_Api_Rest(
                $this->_getHelper()->getCommonConfigData('rest_url', $storeId),
                $this->_getHelper()->getCommonConfigData('site_id', $storeId),
                $this->_getRestHelper()->getPassword($storeId)
            );

            $cancelPaymentResponse = $client->post('V4/Transaction/CancelOrRefund', json_encode($requestData));

            $this->_getRestHelper()->checkResult($cancelPaymentResponse, array('CANCELLED'));

            $this->_getHelper()->log("Payment cancelled successfully online for order #{$order->getIncrementId()}.");

            $transactionId = $payment->getCcTransId() . '-' . $cancelPaymentResponse['answer']['transactionDetails']['sequenceNumber'];
            $additionalInfo = array();

            $txn = Mage::getModel('sales/order_payment_transaction')->setOrderPaymentObject($payment)
                ->loadByTxnId($transactionId);
            if ($txn && $txn->getId()) {
                $additionalInfo = $txn->getAdditionalInformation('raw_details_info');
            }

            // New transaction status.
            $additionalInfo['Transaction Status'] = 'CANCELLED';

            $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID;
            $this->_getPaymentHelper()->addTransaction($payment, $transactionType, $transactionId, $additionalInfo);
            return true; // Let Magento cancel order.
        } catch(\UnexpectedValueException $e) {
           $this->_getHelper()->log("[cancelPayment error with code {$e->getCode()}: {$e->getMessage()}.", Zend_Log::ERR);

           Mage::throwException($e->getMessage());
        } catch (Exception $e) {
            $this->_getHelper()->log("Exception with code {$e->getCode()}: {$e->getMessage()}", Zend_Log::ERR);

            if ($e->getCode() <= -1) { // Manage cUrl errors.
                $message = __("Please consult the PayZen logs for more details.");
            } else {
                $message = $e->getMessage();
            }

            $this->_getAdminSession()->addError($message);

            throw $e;
        }
    }

    /**
     * Attempt to validate a payment.
     *
     * @param  Mage_Payment_Model_Info $payment
     */
    public function validatePayment(Mage_Payment_Model_Info $payment)
    {
        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $uuidArray = array();
        $onlineValidatePayment = true;

        // Client did not configure private key in module backend, let Magento offline validate payment.
        if (! $this->_getRestHelper()->getPassword($storeId)) {
            $this->_getHelper()->log("Cannot online validate payment for order #{$order->getIncrementId()}: private key is not configured, let Magento validate payment.");
            $this->_getAdminSession()->addWarning(__('Payment is validated only in Magento. Please, consider validating the payment in PayZen Back Office.'));
            $onlineValidatePayment = false;
        } else {
            $this->_getHelper()->log("Validate payment online for order #{$order->getIncrementId()}.");
        }

        try {
            // Get choosen payment option if any.
            $option = @unserialize($payment->getAdditionalData());
            $multi = (stripos($payment->getMethod(), 'payzen_multi') === 0) && is_array($option) && ! empty($option);
            $count = $multi ? (int) $option['count'] : 1;

            if ($onlineValidatePayment) {
                // Retrieve saved transaction UUID.
                $savedUuid = $payment->getAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::TRANS_UUID);
                $uuidArray[1] = $savedUuid;

                if (! $savedUuid || ($count > 1)) {
                    $uuidArray = $this->_getPaymentDetails($order);
                } else {
                    $uuidArray[] = $savedUuid;
                }

                $commentText = $this->_getUserInfo();

                $first = true;
                foreach ($uuidArray as $uuid) {
                    $requestData = array(
                        'uuid' => $uuid,
                        'comment' => $commentText
                    );

                    // Perform our request.
                    $client = new Lyranetwork_Payzen_Model_Api_Rest(
                        $this->_getHelper()->getCommonConfigData('rest_url', $storeId),
                        $this->_getHelper()->getCommonConfigData('site_id', $storeId),
                        $this->_getRestHelper()->getPassword($storeId)
                    );

                    $validatePaymentResponse = $client->post('V4/Transaction/Validate', json_encode($requestData));

                    $this->_getRestHelper()->checkResult($validatePaymentResponse, array('WAITING_AUTHORISATION', 'AUTHORISED'));

                    // Wrap payment result to use traditional order creation tunnel.
                    $data = $this->_getRestHelper()->convertRestResult($validatePaymentResponse['answer'], true);

                    // Load API response.
                    $response = new Lyranetwork_Payzen_Model_Api_Response($data, null, null, null);

                    $transId = $order->getPayment()->getCcTransId() . '-' . $response->get('sequence_number');

                    if ($first) { // Single payment or first transaction for payment in installments.
                        $stateObject = $this->_getPaymentHelper()->nextOrderState($response, $order, true);

                        $this->_getHelper()->log("Order #{$order->getIncrementId()}, new state : {$stateObject->getState()}, new status : {$stateObject->getStatus()}.");
                        $order->setState(
                            $stateObject->getState(),
                            $stateObject->getStatus(),
                            $this->_getHelper()->__('Transaction %s has been validated.', $transId)
                        );
                    } else {
                        $order->addStatusHistoryComment($this->_getHelper()->__('Transaction %s has been validated.', $transId));
                    }

                    // Update transaction status.
                    $this->_getHelper()->log("Updating payment information for validated order #{$order->getIncrementId()}.");

                    // Load Magento payment transaction object.
                    $txn = Mage::getModel('sales/order_payment_transaction')->setOrderPaymentObject($payment)
                        ->loadByTxnId($transId);
                    if ($txn && $txn->getId()) {
                        $data = $txn->getAdditionalInformation('raw_details_info');
                        $data['Transaction Status'] = $response->getTransStatus();
                        $data['Transaction UUID'] = $uuid;

                        $txn->setAdditionalInformation('raw_details_info', $data);
                        $txn->save();
                    }

                    $first = false;
                }
            } else {
                // Load Magento payment transaction object.
                $txn = Mage::getModel('sales/order_payment_transaction')
                    ->setOrderPaymentObject($payment)
                    ->loadByTxnId($order->getPayment()->getLastTransId());

                // Wrap payment result to use traditional order creation tunnel.
                $data = array('vads_trans_status' => 'AUTHORISED');

                if ($txn && $txn->getId()) {
                    $txnData = $txn->getAdditionalInformation('raw_details_info');
                    $data['vads_card_brand'] = $txnData['Means of Payment'];
                }

                // Load API response.
                $response = new Lyranetwork_Payzen_Model_Api_Response($data, null, null, null);

                $stateObject = $this->_getPaymentHelper()->nextOrderState($response, $order, true);

                $this->_getHelper()->log("Order #{$order->getIncrementId()}, new state: {$stateObject->getState()}, new status: {$stateObject->getStatus()}.");
                $order->setState(
                    $stateObject->getState(),
                    $stateObject->getStatus(),
                    $this->_getHelper()->__('Order %s has been validated.', $order->getIncrementId())
                );
            }

            $this->_getHelper()->log("Updating payment information for validated order #{$order->getIncrementId()}.");

            // Try to create invoice.
            $this->_getPaymentHelper()->createInvoice($order);

            $order->save();
            $this->_getAdminSession()->addSuccess(__('Payment validated successfully.'));
        } catch(UnexpectedValueException $e) {
            $this->_getHelper()->log("validatePayment error with code {$e->getCode()}: {$e->getMessage()}.", Zend_Log::ERR);

            $this->_getAdminSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getHelper()->log("Exception with code {$e->getCode()}: {$e->getMessage()}", Zend_Log::ERR);

            $message = __('Failed to update the payment.');
            if ($e->getCode() <= -1) { // Manage cUrl errors.
                $message .= '<br>' . __('Please consult the PayZen logs for more details.');
            } else {
                $message .= ' <br>' . $e->getMessage();
            }

            $this->_getAdminSession()->addError($message);
        }
    }

    /**
     * Validate payment method information object.
     * @deprecated to be removed in the next version.
     *
     * @param  Mage_Payment_Model_Info $info
     * @return Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        if ($this->_proposeOney()) {
            $info = $this->getInfoInstance();
            if ($info instanceof Mage_Sales_Model_Order_Payment) {
                $billingAddress = $info->getOrder()->getBillingAddress();
                $shippingAddress = $info->getOrder()->getIsVirtual() ? null : $info->getOrder()->getShippingAddress();
            } else {
                $billingAddress = $info->getQuote()->getBillingAddress();
                $shippingAddress = $info->getQuote()->isVirtual() ? null : $info->getQuote()->getShippingAddress();
            }

            Mage::helper('payzen/util')->checkAddressValidity($billingAddress, 'oney');
            Mage::helper('payzen/util')->checkAddressValidity($shippingAddress, 'oney');

            return $this;
        } else {
            return parent::validate();
        }
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true.
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->_getHelper()->log('Initialize payment called with action ' . $paymentAction);

        if ($paymentAction !== Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE) {
            return;
        }

        $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);

        return $this;
    }

    /**
     * Check method for processing with base currency.
     *
     * @param  string $baseCurrencyCode
     * @return boolean
     */
    public function canUseForCurrency($baseCurrencyCode)
    {
        // Check selected currency support.
        if ($this->currentCurrencyCode) {
            // If submodule support specific currencies, check quote currency over them.
            if (is_array($this->_currencies) && ! empty($this->_currencies)) {
                return in_array($this->currentCurrencyCode, $this->_currencies);
            }

            $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($this->currentCurrencyCode);
            if ($currency) {
                return true;
            }
        }

        // Check base currency support.
        $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($baseCurrencyCode);
        if ($currency) {
            return true;
        }

        $this->_getHelper()->log("Could not find numeric codes for selected ($this->currentCurrencyCode) and base ($baseCurrencyCode) currencies.");
        return false;
    }

    /**
     * Return true if the method can be used at this time.
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (! parent::isAvailable($quote)) {
            return false;
        }

        $this->currentCurrencyCode = $quote ? $quote->getQuoteCurrencyCode() : null;

        $amount = $quote ? $quote->getBaseGrandTotal() : null;
        if (! $amount) {
            return true;
        }

        $configOptions = unserialize($this->getConfigData('custgroup_amount_restrictions'));
        if (! is_array($configOptions) || empty($configOptions)) {
            return true;
        }

        $group = $quote && $quote->getCustomer() ? $quote->getCustomer()->getGroupId() : null;

        $allMinAmount = null;
        $allMaxAmount = null;
        $minAmount = null;
        $maxAmount = null;
        $flag = 0;
        foreach ($configOptions as $value) {
            if (empty($value)) {
                continue;
            }

            if ($value['code'] === 'all') {
                $allMinAmount = $value['amount_min'];
                $allMaxAmount = $value['amount_max'];
                $flag++;
            } elseif ($value['code'] === $group) {
                $minAmount = $value['amount_min'];
                $maxAmount = $value['amount_max'];
                $flag++;
            }

            if ($flag === 2) { // All needed minimum amounts are initialized.
                break;
            }
        }

        if (! $minAmount) {
            $minAmount = $allMinAmount;
        }

        if (! $maxAmount) {
            $maxAmount = $allMaxAmount;
        }

        if (($minAmount && ($amount < $minAmount))
            || ($maxAmount && ($amount > $maxAmount))
        ) {
            // Module will not be available.
            return false;
        }

        return true;
    }

    /**
     * Refund money.
     *
     * @param  Varien_Object $payment
     * @param  float         $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $successStatuses = array_merge(
            Lyranetwork_Payzen_Model_Api_Api::getSuccessStatuses(),
            Lyranetwork_Payzen_Model_Api_Api::getPendingStatuses()
        );

        $this->_getHelper()->log("Start refund of {$amount} {$order->getOrderCurrencyCode()} for order #{$order->getIncrementId()} with {$this->_code} payment method.");

        try {
            // Get currency.
            $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($order->getOrderCurrencyCode());
            $amountInCents = $currency->convertAmountToInteger($amount);

            // Retrieve transaction UUID.
            $uuid = $payment->getAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::TRANS_UUID);
            if (! $uuid) { // Retro compatibility.
                // Get UUID from Order.
                $uuidArray = $this->_getPaymentDetails($order);
                $uuid = reset($uuidArray);
            }

            $requestData = array('uuid' => $uuid);

            // Perform our request.
            $client = new Lyranetwork_Payzen_Model_Api_Rest(
                $this->_getHelper()->getCommonConfigData('rest_url', $storeId),
                $this->_getHelper()->getCommonConfigData('site_id', $storeId),
                $this->_getRestHelper()->getPassword($storeId)
            );

            $getPaymentDetails = $client->post('V4/Transaction/Get', json_encode($requestData));
            $this->_getRestHelper()->checkResult($getPaymentDetails);

            $transStatus = $getPaymentDetails['answer']['detailedStatus'];

            if (! in_array($transStatus, $successStatuses)) {
                $msg = $this->_getHelper()->__('Error occurred when refunding payment for order #%s. Unexpected transaction status: %s.', $order->getIncrementId(), $transStatus);
                Mage::throwException($msg);
            }

            $commentText = $this->_getUserInfo();

            foreach ($payment->getCreditmemo()->getCommentsCollection() as $comment) {
                $commentText .= '; ' . $comment->getComment();
            }

            if ($transStatus === 'CAPTURED') { // Transaction captured.
                $requestData = array(
                    'uuid' => $uuid,
                    'amount' => $amountInCents,
                    'resolutionMode' => 'REFUND_ONLY',
                    'currency' => $currency->getAlpha3(),
                    'comment' => $commentText
                );

                $refundPaymentResponse = $client->post('V4/Transaction/CancelOrRefund', json_encode($requestData));

                $this->_getRestHelper()->checkResult(
                    $refundPaymentResponse,
                    array(
                        'INITIAL',
                        'AUTHORISED',
                        'AUTHORISED_TO_VALIDATE',
                        'WAITING_AUTHORISATION',
                        'WAITING_AUTHORISATION_TO_VALIDATE',
                        'CAPTURED',
                        'UNDER_VERIFICATION'
                    )
                );

                // Check operation type.
                $transType = $refundPaymentResponse['answer']['operationType'];

                if ($transType != 'CREDIT') {
                    $msg = $this->_getHelper()->__("Unexpected transaction type received (%s).", $transType);
                    Mage::throwException($msg);
                }

                // Create refund transaction in Magento.
                $this->_createRefundTransaction($payment, $refundPaymentResponse['answer']);

                $this->_getHelper()->log("Online money refund for order #{$order->getIncrementId()} is successful.");
            } else {
                $transAmount = $getPaymentDetails['answer']['amount'];
                if ($getPaymentDetails['answer']['transactionDetails']['effectiveCurrency'] && ($getPaymentDetails['answer']['transactionDetails']['effectiveCurrency'] !== $getPaymentDetails['answer']['currency'])) {
                    $transAmount = $getPaymentDetails['answer']['transactionDetails']['effectiveAmount']; // Use effective amount to get modified amount.
                }

                if ($amountInCents > $transAmount) {
                    $transAmountFloat = $currency->convertAmountToFloat($transAmount);
                    $msg = $this->_getHelper()->__("Cannot refund payment for order #%s. Transaction amount (%s %s) is less than requested refund amount (%s %s).", $order->getIncrementId(), $transAmountFloat, $currency->getAlpha3(), $amount, $currency->getAlpha3());
                    Mage::throwException($msg);
                }

                if ($amountInCents == $transAmount) { // Transaction cancel.
                    $requestData = array(
                        'uuid' => $uuid,
                        'resolutionMode' => 'CANCELLATION_ONLY',
                        'comment' => $commentText
                    );

                    $cancelPaymentResponse = $client->post('V4/Transaction/CancelOrRefund', json_encode($requestData));

                    $this->_getRestHelper()->checkResult($cancelPaymentResponse, array('CANCELLED'));

                    $order->cancel();
                    $this->_getHelper()->log("Online payment cancel for order #{$order->getIncrementId()} is successful.");
                } else {
                    // Partial transaction cancel, call update WS.
                    $newTransactionAmount = $transAmount - $amountInCents;
                    $requestData = array(
                        'uuid' => $uuid,
                        'cardUpdate' => array(
                            'amount' => $newTransactionAmount,
                            'currency' => $currency->getAlpha3()
                        ),
                        'comment' => $commentText
                    );

                    $updatePaymentResponse = $client->post('V4/Transaction/Update', json_encode($requestData));

                    $successStatuses = array_merge(
                        Lyranetwork_Payzen_Model_Api_Api::getSuccessStatuses(),
                        Lyranetwork_Payzen_Model_Api_Api::getPendingStatuses()
                    );

                    $this->_getRestHelper()->checkResult($updatePaymentResponse, $successStatuses);

                    $this->_getHelper()->log("Online payment update for order #{$order->getIncrementId()} is successful.");
                }
            }
        } catch(UnexpectedValueException $e) {
            $this->_getHelper()->log("refund error with code {$e->getCode()}: {$e->getMessage()}.", Zend_Log::ERR);
            Mage::throwException($e->getMessage());
        } catch (Exception $e) {
            $this->_getHelper()->log(
                "Exception with code {$e->getCode()}: {$e->getMessage()}",
                Zend_Log::ERR
            );

            if ($e->getCode() <= -1) { // Manage cUrl errors.
                $message = __("Please consult the PayZen logs for more details.");
            } else {
                $message = $e->getMessage();
            }

            $this->_getAdminSession()->addError($message);

            throw $e;
        }

        $order->save();
        return $this;
    }

    protected function _createRefundTransaction($payment, $refundResponse)
    {
        $response = $this->_getRestHelper()->convertRestResult($refundResponse, true);

        // Save transaction details to sales_payment_transaction.
        $transactionId = $response['vads_trans_id'] . '-' . $response['vads_sequence_number'];

        $expiry = '';
        if ($response['vads_expiry_month'] && $response['vads_expiry_year']) {
            $expiry = str_pad($response['vads_expiry_month'], 2, '0', STR_PAD_LEFT) . ' / ' .
                $response['vads_expiry_year'];
        }

        // Save paid amount.
        $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByNumCode($response['vads_currency']);
        $amount = number_format($currency->convertAmountToFloat($response['vads_amount']), $currency->getDecimals(), ',', ' ');

        $amountDetail = $amount . ' ' . $currency->getAlpha3();

        if ($response['vads_effective_currency'] &&
            ($response['vads_currency'] !== $response['vads_effective_currency'])) {
            $effectiveCurrency = _Lyranetwork_Payzen_Model_Api_Api::findCurrencyByNumCode($response['vads_effective_currency']);

            $effectiveAmount = number_format(
                $effectiveCurrency->convertAmountToFloat($response['vads_effective_amount']),
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
            'Transaction UUID' => $response['vads_trans_uuid'],
            'Transaction Status' => $response['vads_trans_status'],
            'Means of payment' => $response['vads_card_brand'],
            'Card Number' => $response['vads_card_number'],
            'Expiration Date' => $expiry
        );

        $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND;
        $this->_getPaymentHelper()->addTransaction($payment, $transactionType, $transactionId, $additionalInfo);
    }

    /**
     * Set transaction ID into creditmemo for informational purposes.
     *
     * @param  Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param  Mage_Sales_Model_Order_Payment    $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processCreditmemo($creditmemo, $payment)
    {
        $creditmemo->setTransactionId($payment->getCcTransId());
        $creditmemo->setCreditmemoStatus($payment->getCcStatus());

        return $this;
    }

    protected function _getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * Return payzen data helper.
     *
     * @return Lyranetwork_Payzen_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('payzen');
    }

    /**
     * Return payzen payment method helper.
     *
     * @return Mage_Payzen_Helper_Payment
     */
    protected function _getPaymentHelper()
    {
        return Mage::helper('payzen/payment');
    }

    /**
     * Return payzen payment method helper.
     *
     * @return Mage_Payzen_Helper_Rest
     */
    protected function _getRestHelper()
    {
        return Mage::helper('payzen/rest');
    }

    protected function _getPaymentDetails($order, $uuidOnly = true)
    {
        $storeId = $order->getStore()->getId();

        // Get UUIDs from Order.
        $client = new Lyranetwork_Payzen_Model_Api_Rest(
            $this->_getHelper()->getCommonConfigData('rest_url', $storeId),
            $this->_getHelper()->getCommonConfigData('site_id', $storeId),
            $this->_getRestHelper()->getPassword($storeId)
        );

        $requestData = array(
            'orderId' => $order->getIncrementId(),
            'operationType' => 'DEBIT'
        );

        $getOrderResponse = $client->post('V4/Order/Get', json_encode($requestData));
        $this->_getRestHelper()->checkResult($getOrderResponse);

        // Order transactions organized by sequence numbers.
        $transBySequence = array();
        foreach ($getOrderResponse['answer']['transactions'] as $transaction) {
            $sequenceNumber = $transaction['transactionDetails']['sequenceNumber'];
            // Unpaid transactions are not considered.
            if ($transaction['status'] !== 'UNPAID') {
                $transBySequence[$sequenceNumber] = $uuidOnly ? $transaction['uuid'] : $transaction;
            }
        }

        ksort($transBySequence);
        return $transBySequence;
    }

    protected function _getUserInfo()
    {
        $user = Mage::getSingleton('admin/session');
        $commentText = 'Magento user: ' . $user->getUser()->getUsername();
        $commentText .= '; IP address: ' . $this->_getHelper()->getIpAddress();

        return $commentText;
    }
}
