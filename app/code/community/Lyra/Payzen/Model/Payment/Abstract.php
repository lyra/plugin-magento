<?php
/**
 * PayZen V2-Payment Module version 1.9.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
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

abstract class Lyra_Payzen_Model_Payment_Abstract extends Mage_Payment_Model_Method_Abstract
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

    public function __construct()
    {
        parent::__construct();

        $this->_payzenRequest = new Lyra_Payzen_Model_Api_Request();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return <string:mixed> array of params as key=>value
     */
    public function getFormFields($order)
    {
        // set order_id
        $this->_payzenRequest->set('order_id', $order->getIncrementId());

        // Amount in current order currency
        $amount = $order->getGrandTotal();

        // set currency
        $currency = Lyra_Payzen_Model_Api_Api::findCurrencyByAlphaCode($order->getOrderCurrencyCode());
        if ($currency == null) {
            // If currency is not supported, use base currency
            $currency = Lyra_Payzen_Model_Api_Api::findCurrencyByAlphaCode($order->getBaseCurrencyCode());

            // ... and order total in base currency
            $amount = $order->getBaseGrandTotal();
        }
        $this->_payzenRequest->set('currency', $currency->getNum());

        // set the amount to pay
        $this->_payzenRequest->set('amount', $currency->convertAmountToInteger($amount));

        $this->_payzenRequest->set('contrib', 'Magento1.4-1.9_1.9.0/' . Mage::getVersion() . '/' . PHP_VERSION);

        // set config parameters
        $configFields = array('site_id', 'key_test', 'key_prod', 'ctx_mode', 'capture_delay', 'validation_mode',
            'theme_config', 'shop_name', 'shop_url', 'redirect_enabled', 'redirect_success_timeout',
            'redirect_success_message', 'redirect_error_timeout', 'redirect_error_message', 'return_mode',
            'sign_algo'
        );
        foreach ($configFields as $field) {
            $this->_payzenRequest->set($field, $this->_getHelper()->getCommonConfigData($field));
        }

        // check if capture_delay and validation_mode are overriden in sub-modules
        if (is_numeric($this->getConfigData('capture_delay'))) {
            $this->_payzenRequest->set('capture_delay', $this->getConfigData('capture_delay'));
        }

        if ($this->getConfigData('validation_mode') !== '-1') {
            $this->_payzenRequest->set('validation_mode', $this->getConfigData('validation_mode'));
        }

        // set return url (build it and add store_id)
        $admin = $this->_getHelper()->isAdmin();
        $storeId = $admin ? 0 : $order->getStore()->getId();
        $path = $admin ? 'adminhtml/payzen_payment/return' : 'payzen/payment/return';
        $returnUrl = $this->_getHelper()->prepareUrl($path, $storeId, $admin);

        $this->_getHelper()->log('The complete return URL is ' . $returnUrl);
        $this->_payzenRequest->set('url_return', $returnUrl);

        // set the language code
        $lang = strtolower(substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        if (! Lyra_Payzen_Model_Api_Api::isSupportedLanguage($lang)) {
            $lang = $this->_getHelper()->getCommonConfigData('language');
        }
        $this->_payzenRequest->set('language', $lang);

        // available_languages is given as csv by magento
        $availableLanguages = explode(',', $this->_getHelper()->getCommonConfigData('available_languages'));
        $availableLanguages = in_array('', $availableLanguages) ? '' : implode(';', $availableLanguages);
        $this->_payzenRequest->set('available_languages', $availableLanguages);

        // activate 3ds ?
        $threedsMpi = null;
        $configOptions = unserialize($this->_getHelper()->getCommonConfigData('custgroup_threeds_min_amount'));
        if (is_array($configOptions) && ! empty($configOptions)) {
            $group = $order->getCustomerGroupId();

            $allThreedsMinAmount = null;
            $threedsMinAmount = null;
            $flag = 0;
            foreach ($configOptions as $key => $value) {
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

                if ($flag === 2) { // both $allThreedsMinAmount and $threedsMinAmount are initialized
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
        $this->_payzenRequest->set('cust_state', $order->getBillingAddress()->getRegion());
        $this->_payzenRequest->set('cust_country', $order->getBillingAddress()->getCountryId());
        $this->_payzenRequest->set('cust_phone', $order->getBillingAddress()->getTelephone());

        $address = $order->getShippingAddress();
        if (is_object($address)) { // shipping is supported
            $this->_payzenRequest->set('ship_to_first_name', $address->getFirstname());
            $this->_payzenRequest->set('ship_to_last_name', $address->getLastname());
            $this->_payzenRequest->set('ship_to_city', $address->getCity());
            $this->_payzenRequest->set('ship_to_street', $address->getStreet(1));
            $this->_payzenRequest->set('ship_to_street2', $address->getStreet(2));
            $this->_payzenRequest->set('ship_to_state', $address->getRegion());
            $this->_payzenRequest->set('ship_to_country', $address->getCountryId());
            $this->_payzenRequest->set('ship_to_phone_num', $address->getTelephone());
            $this->_payzenRequest->set('ship_to_zip', $address->getPostcode());
        }

        if ($admin) {
            $session = Mage::getSingleton('adminhtml/session_quote');
            $sendEmail = $session->getPayzenCanSendNewEmail(true);
            $this->_payzenRequest->set('order_info', 'send_confirmation=' . $sendEmail);
        }

        // set method-specific parameters
        $this->_setExtraFields($order);

        // add cart data
        Mage::helper('payzen/util')->setCartData($order, $this->_payzenRequest);

        // set other data specific to FacilyPay Oney payment ond risk assessment module
        Mage::helper('payzen/util')->setAdditionalShippingData($order, $this->_payzenRequest, $this->_proposeOney());

        $paramsToLog = $this->_payzenRequest->getRequestFieldsArray(true);
        $this->_getHelper()->log('Payment parameters : ' . print_r($paramsToLog, true));

        return $this->_payzenRequest->getRequestFieldsArray();
    }

    abstract protected function _setExtraFields($order);

    protected function _proposeOney()
    {
        return false;
    }

    /**
     * Retrieve information from payment configuration.
     *
     * @param string $field
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
     * Attempt to accept a pending payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return bool
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        parent::acceptPayment($payment);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $this->_getHelper()->log("Get payment information online for order #{$order->getId()}.");

        $requestId = '';

        try {
            $wsApi = $this->checkAndGetWsApi($storeId);

            $sid = false;

            // retrieve transaction UUID
            $uuid = $payment->getAdditionalInformation(Lyra_Payzen_Helper_Payment::TRANS_UUID);
            if (! $uuid) { // retro compatibility
                $legacyTransactionKeyRequest = new \Lyra\Payzen\Model\Api\Ws\LegacyTransactionKeyRequest();
                $legacyTransactionKeyRequest->setTransactionId($payment->getCcTransId());
                $legacyTransactionKeyRequest->setSequenceNumber('1');
                $legacyTransactionKeyRequest->setCreationDate(new DateTime($order->getCreatedAt()));

                $getPaymentUuid = new \Lyra\Payzen\Model\Api\Ws\GetPaymentUuid();
                $getPaymentUuid->setLegacyTransactionKeyRequest($legacyTransactionKeyRequest);

                $requestId = $wsApi->setHeaders();
                $getPaymentUuidResponse = $wsApi->getPaymentUuid($getPaymentUuid);

                $wsApi->checkAuthenticity();
                $wsApi->checkResult($getPaymentUuidResponse->getLegacyTransactionKeyResult()->getCommonResponse());

                $uuid = $getPaymentUuidResponse->getLegacyTransactionKeyResult()->getPaymentResponse()->getTransactionUuid();

                // retrieve JSESSIONID created for getPaymentUuid call
                $sid = $wsApi->getJsessionId();
            }

            // common $queryRequest object to use in all operations
            $queryRequest = new \Lyra\Payzen\Model\Api\Ws\QueryRequest();
            $queryRequest->setUuid($uuid);

            $getPaymentDetails = new \Lyra\Payzen\Model\Api\Ws\GetPaymentDetails($queryRequest);
            $getPaymentDetails->setQueryRequest($queryRequest);

            $requestId = $wsApi->setHeaders();

            // set JSESSIONID if ws getPaymentUuid is called
            if ($sid) {
                $wsApi->setJsessionId($sid);
            }

            $getPaymentDetailsResponse = $wsApi->getPaymentDetails($getPaymentDetails);

            $wsApi->checkAuthenticity();
            $wsApi->checkResult(
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getCommonResponse(),
                array(
                    'INITIAL', 'WAITING_AUTHORISATION', 'WAITING_AUTHORISATION_TO_VALIDATE', 'UNDER_VERIFICATION',
                    'AUTHORISED', 'AUTHORISED_TO_VALIDATE', 'CAPTURED', 'CAPTURE_FAILED'
                ) // pending or accepted payment
            );

            // check operation type (0: debit, 1 refund)
            $transType = $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getPaymentResponse()->getOperationType();
            if ($transType != 0) {
                throw new Exception("Unexpected transaction type returned ($transType).");
            }

            $this->_getHelper()->log("Updating payment information for accepted order #{$order->getId()}.");

            // payment is accepted by merchant
            $payment->setIsFraudDetected(false);

            $wrapper = new Lyra_Payzen_Model_Api_Ws_ResultWrapper(
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getCommonResponse(),
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getPaymentResponse(),
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getAuthorizationResponse(),
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getCardResponse(),
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getThreeDSResponse(),
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getFraudManagementResponse()
            );
            $stateObject = $this->_getPaymentHelper()->nextOrderState($wrapper, $order, true);

            $this->_getHelper()->log("Order #{$order->getId()}, new state : {$stateObject->getState()}, new status : {$stateObject->getStatus()}.");
            $order->setState($stateObject->getState(), $stateObject->getStatus(), $this->_getHelper()->__('The payment has been accepted.'));

            // try to create invoice
            $this->_getPaymentHelper()->createInvoice($order);

            $order->save();
            $this->_getAdminSession()->addSuccess($this->_getHelper()->__('The payment has been accepted.'));

            $redirectUrl = Mage::getUrl('*/sales_order/view', array('order_id' => $order->getId()));
            Mage::app()->getResponse()->setRedirect($redirectUrl)->sendHeadersAndExit();
        } catch(Lyra_Payzen_Model_WsException $e) {
            $this->_getHelper()->log("[$requestId] {$e->getMessage()}", Zend_Log::WARN);

            $this->_getAdminSession()->addWarning($this->_getHelper()->__('Please correct this error to use PayZen web services.'));
            $this->_getAdminSession()->addError($this->_getHelper()->__($e->getMessage()));
            return true;
        } catch(\SoapFault $f) {
            $this->_getHelper()->log("[$requestId] SoapFault with code {$f->faultcode}: {$f->faultstring}.", Zend_Log::WARN);

            $this->_getAdminSession()->addWarning($this->_getHelper()->__('Please correct this error to use PayZen web services.'));
            $this->_getAdminSession()->addError($f->faultstring);
            return true;
        } catch(\UnexpectedValueException $e) {
            $this->_getHelper()->log("[$requestId] getPaymentDetails error with code {$e->getCode()}: {$e->getMessage()}.", Zend_Log::ERR);

            if ($e->getCode() === -1) {
                Mage::throwException($this->_getHelper()->__('Authentication error ! '));
            } elseif ($e->getCode() === 1) {
                // merchant does not subscribe to WS option, accept payment offline
                return true;
            } else {
                Mage::throwException($e->getMessage());
            }
        } catch (Exception $e) {
            $this->_getHelper()->log("[$requestId] Exception with code {$e->getCode()}: {$e->getMessage()}", Zend_Log::ERR);

            Mage::throwException($e->getMessage());
        }
    }

    /**
     * Attempt to deny a pending payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return bool
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        parent::denyPayment($payment);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $this->_getHelper()->log("Cancel payment online for order #{$order->getId()}.");

        $requestId = '';

        try {
            $wsApi = $this->checkAndGetWsApi($storeId);

            $sid = false;

            // retrieve transaction UUID
            $uuid = $payment->getAdditionalInformation(Lyra_Payzen_Helper_Payment::TRANS_UUID);
            if (! $uuid) { // retro compatibility
                $legacyTransactionKeyRequest = new \Lyra\Payzen\Model\Api\Ws\LegacyTransactionKeyRequest();
                $legacyTransactionKeyRequest->setTransactionId($payment->getCcTransId());
                $legacyTransactionKeyRequest->setSequenceNumber('1');
                $legacyTransactionKeyRequest->setCreationDate(new DateTime($order->getCreatedAt()));

                $getPaymentUuid = new \Lyra\Payzen\Model\Api\Ws\GetPaymentUuid();
                $getPaymentUuid->setLegacyTransactionKeyRequest($legacyTransactionKeyRequest);

                $requestId = $wsApi->setHeaders();
                $getPaymentUuidResponse = $wsApi->getPaymentUuid($getPaymentUuid);

                $wsApi->checkAuthenticity();
                $wsApi->checkResult($getPaymentUuidResponse->getLegacyTransactionKeyResult()->getCommonResponse());

                $uuid = $getPaymentUuidResponse->getLegacyTransactionKeyResult()->getPaymentResponse()->getTransactionUuid();

                // retrieve JSESSIONID created for getPaymentUuid call
                $sid = $wsApi->getJsessionId();
            }

            // common $queryRequest object to use in all operations
            $queryRequest = new \Lyra\Payzen\Model\Api\Ws\QueryRequest();
            $queryRequest->setUuid($uuid);

            $cancelPayment = new \Lyra\Payzen\Model\Api\Ws\CancelPayment();
            $cancelPayment->setCommonRequest(new \Lyra\Payzen\Model\Api\Ws\CommonRequest());
            $cancelPayment->setQueryRequest($queryRequest);

            $requestId = $wsApi->setHeaders();

            // set JSESSIONID if ws getPaymentUuid is called
            if ($sid) {
                $wsApi->setJsessionId($sid);
            }

            $cancelPaymentResponse = $wsApi->cancelPayment($cancelPayment);

            $wsApi->checkAuthenticity();
            $wsApi->checkResult(
                $cancelPaymentResponse->getCancelPaymentResult()->getCommonResponse(),
                array('CANCELLED')
            );

            $this->_getHelper()->log("Payment cancelled successfully online for order #{$order->getId()}.");

            $transactionId = $payment->getCcTransId() . '-1';
            $additionalInfo = array();

            $txn = Mage::getModel('sales/order_payment_transaction')->setOrderPaymentObject($payment)
                                                                    ->loadByTxnId($transactionId);
            if ($txn && $txn->getId()) {
                $additionalInfo = $txn->getAdditionalInformation('raw_details_info');
            }

            // new transaction status
            $additionalInfo['Transaction Status'] = 'CANCELLED';

            $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID;
            $this->_getPaymentHelper()->addTransaction($payment, $transactionType, $transactionId, $additionalInfo);
            return true; // let Magento cancel order

        } catch(Lyra_Payzen_Model_WsException $e) {
            $this->_getHelper()->log("[$requestId] {$e->getMessage()}", Zend_Log::WARN);

            $this->_getAdminSession()->addWarning($this->_getHelper()->__('Please correct this error to use PayZen web services.'));
            $this->_getAdminSession()->addError($this->_getHelper()->__($e->getMessage()));
            return true;
        } catch(\SoapFault $f) {
            $this->_getHelper()->log("[$requestId] SoapFault with code {$f->faultcode}: {$f->faultstring}.", Zend_Log::WARN);

            $this->_getAdminSession()->addWarning($this->_getHelper()->__('Please correct this error to use PayZen web services.'));
            $this->_getAdminSession()->addError($f->faultstring);
            return true;
        } catch(\UnexpectedValueException $e) {
            $this->_getHelper()->log("[$requestId] cancelPayment error with code {$e->getCode()}: {$e->getMessage()}.", Zend_Log::ERR);

            if ($e->getCode() === -1) {
                Mage::throwException($this->_getHelper()->__('Authentication error ! '));
            } elseif ($e->getCode() === 1) {
                // merchant does not subscribe to WS option, cancel payment offline
                $notice = $this->_getHelper()->__('You are not authorized to do this action online. Please, consider updating the payment in PayZen Back Office.');
                $this->_getAdminSession()->addNotice($notice);
                return true;
            } else {
                Mage::throwException($e->getMessage());
            }
        } catch (Exception $e) {
            $this->_getHelper()->log("[$requestId] Exception with code {$e->getCode()}: {$e->getMessage()}", Zend_Log::ERR);

            Mage::throwException($e->getMessage());
        }
    }

    public function validatePayment(Mage_Payment_Model_Info $payment)
    {
        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $this->_getHelper()->log("Validate payment online for order #{$order->getId()}.");

        $requestId = '';

        try {
            $wsApi = $this->checkAndGetWsApi($storeId);

            $sid = false;

            // retrieve transaction UUID
            $uuid = $payment->getAdditionalInformation(Lyra_Payzen_Helper_Payment::TRANS_UUID);
            if (! $uuid) { // retro compatibility
                $legacyTransactionKeyRequest = new \Lyra\Payzen\Model\Api\Ws\LegacyTransactionKeyRequest();
                $legacyTransactionKeyRequest->setTransactionId($payment->getCcTransId());
                $legacyTransactionKeyRequest->setSequenceNumber('1');
                $legacyTransactionKeyRequest->setCreationDate(new DateTime($order->getCreatedAt()));

                $getPaymentUuid = new \Lyra\Payzen\Model\Api\Ws\GetPaymentUuid();
                $getPaymentUuid->setLegacyTransactionKeyRequest($legacyTransactionKeyRequest);

                $requestId = $wsApi->setHeaders();
                $getPaymentUuidResponse = $wsApi->getPaymentUuid($getPaymentUuid);

                $wsApi->checkAuthenticity();
                $wsApi->checkResult($getPaymentUuidResponse->getLegacyTransactionKeyResult()->getCommonResponse());

                $uuid = $getPaymentUuidResponse->getLegacyTransactionKeyResult()->getPaymentResponse()->getTransactionUuid();

                // retrieve JSESSIONID created for getPaymentUuid call
                $sid = $wsApi->getJsessionId();
            }

            // common $queryRequest object to use in all operations
            $queryRequest = new \Lyra\Payzen\Model\Api\Ws\QueryRequest();
            $queryRequest->setUuid($uuid);

            $validatePayment = new \Lyra\Payzen\Model\Api\Ws\ValidatePayment();
            $validatePayment->setCommonRequest(new \Lyra\Payzen\Model\Api\Ws\CommonRequest());
            $validatePayment->setQueryRequest($queryRequest);

            $requestId = $wsApi->setHeaders();

            // set JSESSIONID if ws getPaymentUuid is called
            if ($sid) {
                $wsApi->setJsessionId($sid);
            }

            $validatePaymentResponse = $wsApi->validatePayment($validatePayment);

            $wsApi->checkAuthenticity();
            $wsApi->checkResult(
                $validatePaymentResponse->getValidatePaymentResult()->getCommonResponse(),
                array('WAITING_AUTHORISATION', 'AUTHORISED')
            );

            $this->_getHelper()->log("Updating payment information for validated order #{$order->getId()}.");

            $wrapper = new Lyra_Payzen_Model_Api_Ws_ResultWrapper($validatePaymentResponse->getValidatePaymentResult()->getCommonResponse());
            $stateObject = $this->_getPaymentHelper()->nextOrderState($wrapper, $order, true);

            $this->_getHelper()->log("Order #{$order->getId()}, new state : {$stateObject->getState()}, new status : {$stateObject->getStatus()}.");
            $order->setState(
                $stateObject->getState(),
                $stateObject->getStatus(),
                $this->_getHelper()->__('Payment validated successfully.')
            );

            // update transaction status
            $txn = Mage::getModel('sales/order_payment_transaction')->setOrderPaymentObject($order->getPayment())
                                                                    ->loadByTxnId($order->getPayment()->getCcTransId() . '-1');
            if ($txn && $txn->getId()) {
                $data = $txn->getAdditionalInformation('raw_details_info');
                $data['Transaction Status'] = $wrapper->getTransStatus();

                $txn->setAdditionalInformation('raw_details_info', $data);
                $txn->save();
            }

            // try to create invoice
            $this->_getPaymentHelper()->createInvoice($order);

            $order->save();

            $this->_getAdminSession()->addSuccess($this->_getHelper()->__('Payment validated successfully.'));

        } catch(Lyra_Payzen_Model_WsException $e) {
            $this->_getHelper()->log("[$requestId] {$e->getMessage()}", Zend_Log::WARN);

            $this->_getAdminSession()->addWarning($this->_getHelper()->__('Please correct this error to use PayZen web services.'));
            $this->_getAdminSession()->addError($this->_getHelper()->__($e->getMessage()));
        } catch(\SoapFault $f) {
            $this->_getHelper()->log("[$requestId] SoapFault with code {$f->faultcode}: {$f->faultstring}.", Zend_Log::WARN);

            $this->_getAdminSession()->addWarning($this->_getHelper()->__('Please correct this error to use PayZen web services.'));
            $this->_getAdminSession()->addError($f->faultstring);
        } catch(\UnexpectedValueException $e) {
            $this->_getHelper()->log("[$requestId] validatePayment error with code {$e->getCode()}: {$e->getMessage()}.", Zend_Log::ERR);

            if ($e->getCode() === -1) {
                $this->_getAdminSession()->addError($this->_getHelper()->__('Authentication error ! '));
            } elseif ($e->getCode() === 1) {
                // merchant does not subscribe to WS option, validate payment offline
                $notice = $this->_getHelper()->__('You are not authorized to do this action online. Please, consider updating the payment in PayZen Back Office.');
                $this->_getAdminSession()->addNotice($notice);
            } else {
                $this->_getAdminSession()->addError($e->getMessage());
            }
        } catch (Exception $e) {
            $this->_getHelper()->log("[$requestId] Exception with code {$e->getCode()}: {$e->getMessage()}", Zend_Log::ERR);

            $this->_getAdminSession()->addError($e->getMessage());
        }
    }

    /**
     * Validate payment method information object
     *
     * @param   Mage_Payment_Model_Info $info
     * @return  Mage_Payment_Model_Abstract
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
                $shippingAddress =  $info->getQuote()->isVirtual() ? null : $info->getQuote()->getShippingAddress();
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
     * if flag isInitializeNeeded set to true
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
     * Check method for processing with base currency
     *
     * @param string $baseCurrencyCode
     * @return boolean
     */
    public function canUseForCurrency($baseCurrencyCode)
    {
        // check selected currency support
        $currencyCode = '';
        $quote = $this->_getHelper()->getCheckoutQuote();
        if ($quote && $quote->getId()) {
            $currencyCode = $quote->getQuoteCurrencyCode();

            // if sub-module support specific currencies, check quote currency over them
            if (is_array($this->_currencies) && ! empty($this->_currencies)) {
                return in_array($currencyCode, $this->_currencies);
            }

            $currency = Lyra_Payzen_Model_Api_Api::findCurrencyByAlphaCode($currencyCode);
            if ($currency) {
                return true;
            }
        }

        // check base currency support
        $currency = Lyra_Payzen_Model_Api_Api::findCurrencyByAlphaCode($baseCurrencyCode);
        if ($currency) {
            return true;
        }

        $this->_getHelper()->log("Could not find numeric codes for selected ($currencyCode) and base ($baseCurrencyCode) currencies.");
        return false;
    }

    /**
     * Return true if the method can be used at this time
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (! parent::isAvailable($quote)) {
            return false;
        }

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
        foreach ($configOptions as $key => $value) {
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

            if ($flag === 2) { // all needed minimum amounts are initialized
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
                || ($maxAmount && ($amount > $maxAmount))) {
            // module will not be available
            return false;
        }

        return true;
    }

    /**
     * Refund money.
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $this->_getHelper()->log("Start refund of {$amount} {$order->getOrderCurrencyCode()} for order #{$order->getId()} with {$this->_code} payment method.");

        $requestId = '';

        try {
            $wsApi = $this->checkAndGetWsApi($storeId);

            $sid = false;

            // retrieve transaction UUID
            $uuid = $payment->getAdditionalInformation(Lyra_Payzen_Helper_Payment::TRANS_UUID);
            if (! $uuid) { // retro compatibility
                $legacyTransactionKeyRequest = new \Lyra\Payzen\Model\Api\Ws\LegacyTransactionKeyRequest();
                $legacyTransactionKeyRequest->setTransactionId($payment->getCcTransId());
                $legacyTransactionKeyRequest->setSequenceNumber('1'); // only single payments can be refund at this time
                $legacyTransactionKeyRequest->setCreationDate(new DateTime($order->getCreatedAt()));

                $getPaymentUuid = new \Lyra\Payzen\Model\Api\Ws\GetPaymentUuid();
                $getPaymentUuid->setLegacyTransactionKeyRequest($legacyTransactionKeyRequest);

                $requestId = $wsApi->setHeaders();
                $getPaymentUuidResponse = $wsApi->getPaymentUuid($getPaymentUuid);

                $wsApi->checkAuthenticity();
                $wsApi->checkResult($getPaymentUuidResponse->getLegacyTransactionKeyResult()->getCommonResponse());

                $uuid = $getPaymentUuidResponse->getLegacyTransactionKeyResult()->getPaymentResponse()->getTransactionUuid();

                // retrieve JSESSIONID created for getPaymentUuid call
                $sid = $wsApi->getJsessionId();
            }

            // common $queryRequest object to use in all operations
            $queryRequest = new \Lyra\Payzen\Model\Api\Ws\QueryRequest();
            $queryRequest->setUuid($uuid);

            $getPaymentDetails = new \Lyra\Payzen\Model\Api\Ws\GetPaymentDetails();
            $getPaymentDetails->setQueryRequest($queryRequest);

            $requestId = $wsApi->setHeaders();

            // set JSESSIONID if ws getPaymentUuid is called
            if ($sid) {
                $wsApi->setJsessionId($sid);
            }

            $getPaymentDetailsResponse = $wsApi->getPaymentDetails($getPaymentDetails);

            $wsApi->checkAuthenticity();
            $wsApi->checkResult($getPaymentDetailsResponse->getGetPaymentDetailsResult()->getCommonResponse());

            // retrieve JSESSIONID created for getPaymentDetails call
            if (! $sid) {
                $sid = $wsApi->getJsessionId();
            }

            $transStatus = $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getCommonResponse()->getTransactionStatusLabel();

            // get currency
            $currency = Lyra_Payzen_Model_Api_Api::findCurrencyByAlphaCode($order->getOrderCurrencyCode());
            $amountInCents = $currency->convertAmountToInteger($amount);

            // common request generation
            $commonRequest = new \Lyra\Payzen\Model\Api\Ws\CommonRequest();
            $comment = '';
            foreach ($payment->getCreditmemo()->getCommentsCollection() as $comment) {
                $comment .= $comment->getComment() . ' ';
            }
            $commonRequest->setComment($comment);

            $requestId = $wsApi->setHeaders();
            $wsApi->setJsessionId($sid); // set JSESSIONID for the last WS call

            if ($transStatus === 'CAPTURED') { // transaction captured, we can do refund
                $timestamp = time();

                $paymentRequest = new \Lyra\Payzen\Model\Api\Ws\PaymentRequest();
                $paymentRequest->setTransactionId(Lyra_Payzen_Model_Api_Api::generateTransId($timestamp));
                $paymentRequest->setAmount($amountInCents);
                $paymentRequest->setCurrency($currency->getNum());

                $captureDelay = $this->getConfigData('capture_delay', $storeId); // get sub-module specific param
                if (! is_numeric($captureDelay)) {
                    $captureDelay = $this->_getHelper()->getCommonConfigData('capture_delay', $storeId); // get general param
                }

                if (is_numeric($captureDelay)) {
                    $paymentRequest->setExpectedCaptureDate(new DateTime('@' . strtotime("+$captureDelay days", $timestamp)));
                }

                $validationMode = $this->getConfigData('validation_mode', $storeId); // get sub-module specific param
                if ($validationMode === '-1') {
                    $validationMode = $this->_getHelper()->getCommonConfigData('validation_mode', $storeId); // get general param
                }

                if ($validationMode !== '') {
                    $paymentRequest->setManualValidation($validationMode);
                }

                $refundPayment = new \Lyra\Payzen\Model\Api\Ws\RefundPayment();
                $refundPayment->setCommonRequest($commonRequest);
                $refundPayment->setPaymentRequest($paymentRequest);
                $refundPayment->setQueryRequest($queryRequest);

                $refurndPaymentResponse = $wsApi->refundPayment($refundPayment);

                $wsApi->checkAuthenticity();
                $wsApi->checkResult(
                    $refurndPaymentResponse->getRefundPaymentResult()->getCommonResponse(),
                    array(
                        'INITIAL', 'AUTHORISED', 'AUTHORISED_TO_VALIDATE', 'WAITING_AUTHORISATION',
                        'WAITING_AUTHORISATION_TO_VALIDATE'
                    )
                );

                // check operation type (0: debit, 1 refund)
                $transType = $refurndPaymentResponse->getRefundPaymentResult()->getPaymentResponse()->getOperationType();
                if ($transType != 1) {
                    throw new Exception("Unexpected transaction type returned ($transType).");
                }

                // create refund transaction in Magento
                $this->createRefundTransaction(
                    $payment,
                    $refurndPaymentResponse->getRefundPaymentResult()->getCommonResponse(),
                    $refurndPaymentResponse->getRefundPaymentResult()->getPaymentResponse(),
                    $refurndPaymentResponse->getRefundPaymentResult()->getCardResponse()
                );

                $this->_getHelper()->log("Online money refund for order #{$order->getId()} is successful.");
            } else {
                $transAmount = $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getPaymentResponse()->getAmount();
                if ($amountInCents >= $transAmount) { // transaction cancel
                    $cancelPayment = new \Lyra\Payzen\Model\Api\Ws\CancelPayment();
                    $cancelPayment->setCommonRequest($commonRequest);
                    $cancelPayment->setQueryRequest($queryRequest);

                    $cancelPaymentResponse = $wsApi->cancelPayment($cancelPayment);

                    $wsApi->checkAuthenticity();
                    $wsApi->checkResult($cancelPaymentResponse->getCancelPaymentResult()->getCommonResponse(), array('CANCELLED'));

                    $order->cancel();
                    $this->_getHelper()->log("Online payment cancel for order #{$order->getId()} is successful.");
                } else { // partial transaction cancel, call updatePayment WS
                    $paymentRequest = new \Lyra\Payzen\Model\Api\Ws\PaymentRequest();
                    $paymentRequest->setAmount($transAmount - $amountInCents);
                    $paymentRequest->setCurrency($currency->getNum());

                    $updatePayment = new \Lyra\Payzen\Model\Api\Ws\UpdatePayment();
                    $updatePayment->setCommonRequest($commonRequest);
                    $updatePayment->setQueryRequest($queryRequest);
                    $updatePayment->setPaymentRequest($paymentRequest);

                    $updatePaymentResponse = $wsApi->updatePayment($updatePayment);

                    $wsApi->checkAuthenticity();
                    $wsApi->checkResult(
                        $updatePaymentResponse->getUpdatePaymentResult()->getCommonResponse(),
                        array('AUTHORISED', 'AUTHORISED_TO_VALIDATE', 'WAITING_AUTHORISATION', 'WAITING_AUTHORISATION_TO_VALIDATE')
                    );
                    $this->_getHelper()->log("Online payment update for order #{$order->getId()} is successful.");
                }
            }
        } catch(Lyra_Payzen_Model_WsException $e) {
            $this->_getHelper()->log("[$requestId] {$e->getMessage()}", Zend_Log::WARN);

            $warn = $this->_getHelper()->__('Please correct error to refund payments through PayZen. If you want to refund order in Magento, use the &laquo;Refund Offline&raquo; button.');
            $this->_getAdminSession()->addWarning($warn);
            Mage::throwException($this->_getHelper()->__($e->getMessage()));
        } catch(\SoapFault $f) {
            $this->_getHelper()->log("[$requestId] SoapFault with code {$f->faultcode}: {$f->faultstring}.", Zend_Log::WARN);

            $warn = $this->_getHelper()->__('Please correct error to refund payments through PayZen. If you want to refund order in Magento, use the &laquo;Refund Offline&raquo; button.');
            $this->_getAdminSession()->addWarning($warn);
            Mage::throwException($f->faultstring);
        } catch(\UnexpectedValueException $e) {
            $this->_getHelper()->log("[$requestId] refund error with code {$e->getCode()}: {$e->getMessage()}.", Zend_Log::ERR);

            if ($e->getCode() === -1) {
                Mage::throwException($this->_getHelper()->__('Authentication error ! '));
            } elseif ($e->getCode() === 1) {
                // merchant does not subscribe to WS option, refund payment offline
                $notice = $this->_getHelper()->__('You are not authorized to do this action online. Please, consider updating the payment in PayZen Back Office.');
                $this->_getAdminSession()->addNotice($notice);
                // Magento will do an offline refund
            } else {
                Mage::throwException($e->getMessage());
            }
        } catch (Exception $e) {
            $this->_getHelper()->log("[$requestId] Exception with code {$e->getCode()}: {$e->getMessage()}", Zend_Log::ERR);

            Mage::throwException($e->getMessage());
        }

        $order->save();
        return $this;
    }

    protected function createRefundTransaction($payment, $commonResponse, $paymentResponse, $cardResponse)
    {
        // save transaction details to sales_payment_transaction
        $transactionId = $paymentResponse->getTransactionId() . '-' . $paymentResponse->getSequenceNumber();

        $expiry = '';
        if ($cardResponse->getExpiryMonth() && $cardResponse->getExpiryYear()) {
            $expiry = str_pad($cardResponse->getExpiryMonth(), 2, '0', STR_PAD_LEFT) . ' / ' . $cardResponse->getExpiryYear();
        }

        // save paid amount
        $currency = Lyra_Payzen_Model_Api_Api::findCurrencyByNumCode($paymentResponse->getCurrency());
        $amount = number_format($currency->convertAmountToFloat($paymentResponse->getAmount()), $currency->getDecimals(), ',', ' ');

        $amountDetail = $amount . ' ' . $currency->getAlpha3();

        if ($paymentResponse->getEffectiveCurrency() && ($paymentResponse->getCurrency() !== $paymentResponse->getEffectiveCurrency())) {
            $effectiveCurrency = Lyra_Payzen_Model_Api_Api::findCurrencyByNumCode($paymentResponse->getEffectiveCurrency());

            $effectiveAmount = number_format(
                $effectiveCurrency->convertAmountToFloat($paymentResponse->getEffectiveAmount()),
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
            'Transaction Status' => $commonResponse->getTransactionStatusLabel(),
            'Means of Payment' => $cardResponse->getBrand(),
            'Credit Card Number' => $cardResponse->getNumber(),
            'Expiration Date' => $expiry
        );

        $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND;
        $this->_getPaymentHelper()->addTransaction($payment, $transactionType, $transactionId, $additionalInfo);
    }

    protected function checkAndGetWsApi($storeId)
    {
        $this->_getHelper()->checkWsRequirements();

        // headers generation
        $shopId = $this->_getHelper()->getCommonConfigData('site_id', $storeId);
        $mode = $this->_getHelper()->getCommonConfigData('ctx_mode', $storeId);
        $keyTest = $this->_getHelper()->getCommonConfigData('key_test', $storeId);
        $keyProd = $this->_getHelper()->getCommonConfigData('key_prod', $storeId);

        // load specific configuration file for WSDL access
        $options = parse_ini_file(Mage::getModuleDir('etc', 'Lyra_Payzen') . DS . 'ws.ini') ?: array();

        if (! empty($options)) {
            if (! $options['proxy.enabled']) {
                unset($options['proxy_host'], $options['proxy_port'], $options['proxy_login'], $options['proxy_password']);
            }

            unset($options['proxy.enabled']);
        }

        require_once Mage::getModuleDir('', 'Lyra_Payzen') . DS . 'Model' . DS . 'Api'  . DS . 'Ws'  . DS . 'WsApiClassLoader.php';
        \Lyra\Payzen\Model\Api\Ws\WsApiClassLoader::register(true);

        $wsApi = new \Lyra\Payzen\Model\Api\Ws\WsApi($options);
        $wsApi->init($shopId, $mode, $keyTest, $keyProd);

        return $wsApi;
    }

    /**
    * Set transaction ID into creditmemo for informational purposes.
    *
    * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
    * @param Mage_Sales_Model_Order_Payment $payment
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
     * @return Lyra_Payzen_Helper_Data
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
}
