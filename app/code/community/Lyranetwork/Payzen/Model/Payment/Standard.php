<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Payment_Standard extends Lyranetwork_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_standard';
    protected $_formBlockType = 'payzen/standard';

    protected function _setExtraFields($order)
    {
        $info = $this->getInfoInstance();

        if (! $this->_getHelper()->isAdmin() && $this->isLocalCcType()) {
            // Set payment_cards.
            $this->_payzenRequest->set('payment_cards', $info->getCcType());

            if ($info->getCcType() === 'BANCONTACT') {
                // May not disable 3DS for Bancontact Mistercash.
                $this->_payzenRequest->set('threeds_mpi', null);
            }
        } else {
            // Payment_cards is given as csv by Magento.
            $paymentCards = explode(',', $this->getConfigData('payment_cards'));
            $paymentCards = in_array('', $paymentCards) ? '' : implode(';', $paymentCards);

            if ($paymentCards && $this->getConfigData('use_oney_in_standard')) {
                $testMode = $this->_payzenRequest->get('ctx_mode') === 'TEST';

                // Add FacilyPay Oney payment cards.
                $paymentCards .= ';' . ($testMode ? 'ONEY_SANDBOX' : 'ONEY');
            }

            $this->_payzenRequest->set('payment_cards', $paymentCards);
        }

        if ($this->_getHelper()->isAdmin()) {
            // Set payment_src to MOTO for backend payments.
            $this->_payzenRequest->set('payment_src', 'MOTO');
            return;
        }

        $session = Mage::getSingleton('payzen/session');
        if ($this->isIframeMode() && ! $session->getPayzenOneclickPayment() /* No iframe for 1-Click. */) {
            // Iframe enabled and this is not 1-Click.
            $this->_payzenRequest->set('action_mode', 'IFRAME');

            // Hide logos below payment fields.
            $this->_payzenRequest->set('theme_config', $this->_payzenRequest->get('theme_config') . '3DS_LOGOS=false;');

            // Enable automatic redirection.
            $this->_payzenRequest->set('redirect_enabled', '1');
            $this->_payzenRequest->set('redirect_success_timeout', '0');
            $this->_payzenRequest->set('redirect_error_timeout', '0');

            $returnUrl = $this->_payzenRequest->get('url_return');
            $this->_payzenRequest->set('url_return', $returnUrl . '?iframe=true');
        }

        if ($this->getConfigData('one_click_active') && $order->getCustomerId()) {
            // 1-Click enabled and customer logged-in.
            $customer = Mage::getModel('customer/customer');
            $customer->load($order->getCustomerId());

            if ($customer->getPayzenIdentifier()) {
                // Customer has an identifier.
                $this->_getHelper()->log("Customer {$customer->getEmail()} has an identifier. Use it for payment of order #{$order->getIncrementId()}.");
                $this->_payzenRequest->set('identifier', $customer->getPayzenIdentifier());

                if (! $info->getAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::IDENTIFIER)) {
                    // Customer choose to not use token.
                    $this->_payzenRequest->set('page_action', 'REGISTER_UPDATE_PAY');
                }
            } else {
                // Card data entry on payment page, let's ask customer for data registration.
                $this->_getHelper()->log("Customer {$customer->getEmail()} will be asked for card data registration on payment page for order #{$order->getIncrementId()}.");
                $this->_payzenRequest->set('page_action', 'ASK_REGISTER_PAY');
            }
        }
    }

    protected function _proposeOney()
    {
        $info = $this->getInfoInstance();

        return (! $info->getCcType() && $this->getConfigData('use_oney_in_standard'))
            || in_array($info->getCcType(), array('ONEY_SANDBOX', 'ONEY'));
    }

    private function _getFormTokenData($quote, $useIdentifier = false)
    {
        $amount = $quote->getGrandTotal();
        $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($quote->getOrderCurrencyCode());
        if ($currency == null) {
            // If currency is not supported, use base currency
            $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($quote->getBaseCurrencyCode());

            // ... and order total in base currency.
            $amount = $quote->getBaseGrandTotal();
        }

        $language = strtolower(substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        if (! Lyranetwork_Payzen_Model_Api_Api::isSupportedLanguage($language)) {
            $language = $this->_getHelper()->getCommonConfigData('language');
        }

        // Check if capture_delay and validation_mode are overriden in standard submodule.
        if (is_numeric($this->getConfigData('capture_delay'))) {
            $captureDelay = $this->getConfigData('capture_delay');
        } else {
            $captureDelay = $this->_getHelper()->getCommonConfigData('capture-delay');
        }

        if ($this->getConfigData('validation_mode') !== '-1') {
            $validationMode = $this->getConfigData('validation_mode');
        } else {
            $validationMode = $this->_getHelper()->getCommonConfigData('validation_mode');
        }

        // Activate 3DS ?
        $strongAuth = 'AUTO';
        $threedsMinAmount = $this->_getHelper()->getCommonConfigData('threeds_min_amount');
        if ($threedsMinAmount && $quote->getTotalDue() < $threedsMinAmount) {
            $strongAuth = 'DISABLED';
        }

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        $methodCode = Mage::helper('payzen/util')->toPayzenCarrier($shippingAddress->getShippingMethod());

        $contrib = $this->_getHelper()->getCommonConfigData('cms_identifier') . '_' .
            $this->_getHelper()->getCommonConfigData('plugin_version') . '/';

        if (! $quote->getReservedOrderId()) {
            // Reserve order ID.
            $quote->reserveOrderId()->save();
        }

        $data = array(
            'orderId' => $quote->getReservedOrderId(),
            'customer' => array(
                'email' => $quote->getCustomerEmail(),
                'reference' => $quote->getCustomer()->getId(),
                'billingDetails' => array(
                    'language' => strtoupper($language),
                    'title' => $billingAddress->getPrefix() ? $billingAddress->getPrefix() : null,
                    'firstName' => $billingAddress->getFirstname(),
                    'lastName' => $billingAddress->getLastname(),
                    'address' => $billingAddress->getStreet(1) . ' ' . $billingAddress->getStreet(2),
                    'zipCode' => $billingAddress->getPostcode(),
                    'city' => $billingAddress->getCity(),
                    'state' => !is_numeric($billingAddress->getRegionCode()) ?
                        $billingAddress->getRegionCode() : $billingAddress->getRegion(),
                    'phoneNumber' => $billingAddress->getTelephone(),
                    'country' => $billingAddress->getCountryId()
                ),
                'shippingDetails' => array(
                    'firstName' => $shippingAddress->getFirstname(),
                    'lastName' => $shippingAddress->getLastname(),
                    'address' => $shippingAddress->getStreet(1),
                    'address2' => $shippingAddress->getStreet(2),
                    'zipCode' => $shippingAddress->getPostcode(),
                    'city' => $shippingAddress->getCity(),
                    'state' => !is_numeric($shippingAddress->getRegionCode()) ?
                        $shippingAddress->getRegionCode() : $shippingAddress->getRegion(),
                    'phoneNumber' => $shippingAddress->getTelephone(),
                    'country' => $shippingAddress->getCountryId(),
                    'deliveryCompanyName' => $methodCode['oney_label'],
                    'shippingMethod' => $methodCode['type'],
                    'shippingSpeed' => $methodCode['speed']
                )
            ),
            'transactionOptions' => array(
                'cardOptions' => array(
                    'captureDelay' => $captureDelay,
                    'manualValidation' => $validationMode ? 'YES' : 'NO',
                    'paymentSource' => 'EC'
                )
            ),
            'contrib' => $contrib . Mage::getVersion() . '/' . PHP_VERSION,
            'strongAuthenticationState' => $strongAuth,
            'currency' => $currency->getAlpha3(),
            'amount' => $currency->convertAmountToInteger($amount),
            'metadata' => array(
                'quote_id' => $quote->getId()
            )
        );

        // Set Number of attempts in case of rejected payment.
        if ($this->getConfigData('rest_attempts')) {
            $data['transactionOptions']['cardOptions']['retry'] = $this->getConfigData('rest_attempts');
        }

        $customer = $quote->getCustomer();

        if ($useIdentifier) {
            $this->_getHelper()->log("Customer {$customer->getEmail()} has an identifier. Use it for payment of order #{$quote->getReservedOrderId()}.");
            $data['paymentMethodToken'] = $customer->getPayzenIdentifier();
        } elseif ($this->getConfigData('one_click_active') && $quote->getCustomer()->getId()) {
            // 1-Click enabled and customer logged-in, let's ask customer for card data registration.
            $this->_getHelper()->log("Customer {$customer->getEmail()} will be asked for card data registration on payment page for order #{$quote->getReservedOrderId()}.");
            $data['formAction'] = 'ASK_REGISTER_PAY';
        }

        return $data;
    }

    public function getFormToken($useIdentifier, $renew = false)
    {
        $token = false;

        $session = Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();

        if (! $quote || ! $quote->getId()) {
            $this->_getHelper()->log('Cannot create form token. Empty quote passed.');
            return false;
        }

        $data = $this->_getFormTokenData($quote, $useIdentifier);

        $tokenDataName = Lyranetwork_Payzen_Helper_Payment::TOKEN_DATA . ($useIdentifier ? '_identifier' : '');
        $tokenName = Lyranetwork_Payzen_Helper_Payment::TOKEN . ($useIdentifier ? '_identifier' : '');

        if ($renew) {
            $quote->getPayment()->unsAdditionalInformation($tokenDataName);
            $quote->getPayment()->unsAdditionalInformation($tokenName);
        } else {
            $lastTokenData = $quote->getPayment()->getAdditionalInformation($tokenDataName);
            $lastToken = $quote->getPayment()->getAdditionalInformation($tokenName);

            $tokenData = base64_encode(serialize($data));
            if ($lastToken && ($lastTokenData === $tokenData)) {
                 // Cart data does not change from last payment attempt, do not re-create payment token.
                 $this->_getHelper()->log("Cart data does not change from last payment attempt, use last created token for quote #{$quote->getId()}, reserved order ID #{$quote->getReservedOrderId()}.");
                 return $lastToken;
            }
        }

        $login = $this->_getHelper()->getCommonConfigData('site_id');

        // Perform our request.
        $client = new Lyranetwork_Payzen_Model_Api_Rest(
            $this->_getHelper()->getCommonConfigData('rest_url'),
            $login,
            $this->_getPassword()
        );

        try {
            $response = $client->post('V4/Charge/CreatePayment', json_encode($data));

            if ($response['status'] != 'SUCCESS') {
                $msg = "Error while creating payment form token for quote #{$quote->getId()}, reserved order ID #{$quote->getReservedOrderId()}: ";
                $msg .= $response['answer']['errorMessage'] . ' (' . $response['answer']['errorCode'] . ').';
                $this->_getHelper()->log($msg);

                if (isset($response['answer']['detailedErrorMessage']) && ! empty($response['answer']['detailedErrorMessage'])) {
                    $this->_getHelper()->log('Detailed message: ' . $response['answer']['detailedErrorMessage']
                        . ' (' . $response['answer']['detailedErrorCode'] . ').');
                }

                $token = false;
            } else {
                // Payment form token created successfully.
                $token = $response['answer']['formToken'];
                $tokenData = base64_encode(serialize($data));

                $this->_getHelper()->log("Form token created successfully for quote #{$quote->getId()}, reserved order ID #{$quote->getReservedOrderId()}.");

                $quote->getPayment()->setAdditionalInformation($tokenDataName, $tokenData);
                $quote->getPayment()->setAdditionalInformation($tokenName, $token);
                $quote->getPayment()->save();
            }
        } catch (\Exception $e) {
            $this->_getHelper()->log($e->getMessage());
            $token = false;
        }

        return $token;
    }

    private function _getPassword()
    {
        $test = $this->_getHelper()->getCommonConfigData('ctx_mode') === 'TEST';
        $crypted = $this->getConfigData($test ? 'rest_private_key_test' : 'rest_private_key_prod');

        return Mage::helper('core')->decrypt($crypted);
    }

    /**
     * Return available card types
     *
     * @return string
     */
    public function getAvailableCcTypes()
    {
        // All cards.
        $allCards = Lyranetwork_Payzen_Model_Api_Api::getSupportedCardTypes();

        // Selected cards from module configuration.
        $cards = $this->getConfigData('payment_cards');

        if (! empty($cards)) {
            $cards = explode(',', $cards);
        } else {
            $cards = array_keys($allCards);
            $cards = array_diff($cards, array('ONEY_SANDBOX', 'ONEY'));
        }

        if (! $this->_getHelper()->isAdmin() && $this->isLocalCcType()
            && $this->getConfigData('use_oney_in_standard')
        ) {
            $testMode = $this->_getHelper()->getCommonConfigData('ctx_mode') === 'TEST';

            $cards[] = $testMode ? 'ONEY_SANDBOX' : 'ONEY';
        }

        $availCards = array();
        foreach ($allCards as $code => $label) {
            if (in_array($code, $cards)) {
                $availCards[$code] = $label;
            }
        }

        return $availCards;
    }

    public function isOneclickAvailable()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        // No 1-Click.
        if (! $this->getConfigData('one_click_active')) {
            return false;
        }

        if ($this->_getHelper()->isAdmin()) {
            return false;
        }

        $session = Mage::getSingleton('customer/session');

        // Customer not logged in.
        if (! $session->isLoggedIn()) {
            return false;
        }

        // Customer has not gateway identifier.
        $customer = $session->getCustomer();
        if (! $customer || ! $customer->getPayzenIdentifier()) {
            return false;
        }

        return true;
    }

    /**
     * Assign data to info model instance
     *
     * @param  mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (! ($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        $useIdentifier = $data->getPayzenStandardUseIdentifier();

        $info->setCcType($data->getPayzenStandardCcType())
            ->setCcLast4(null)
            ->setCcNumber(null)
            ->setCcCid(null)
            ->setCcExpMonth(null)
            ->setCcExpYear(null)
            ->setAdditionalData(null)
            ->setAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::IDENTIFIER, $useIdentifier); // Payment by identifier.

        Mage::getSingleton('checkout/session')->setIdentifierPayment($useIdentifier);

        return $this;
    }

    /**
     * Prepare info instance for save
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        $info->setCcNumberEnc(null);
        $info->setCcNumber(null);
        $info->setCcCid(null);

        return $this;
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
        parent::initialize($paymentAction, $stateObject);

        if ($this->_getHelper()->isAdmin() && $this->_getHelper()->isCurrentlySecure()) {
            // Do instant payment by WS.
            $stateObjectResult = $this->_doInstantPayment($this->getInfoInstance());

            $stateObject->setState($stateObjectResult->getState());
            $stateObject->setStatus($stateObjectResult->getStatus());
            $stateObject->setIsNotified($stateObjectResult->getIsNotified());
        }

        return $this;
    }

    /**
     * The URL the customer is redirected to after clicking on "Confirm order".
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        if ($this->isIframeMode()) {
            return Mage::getUrl('payzen/payment/iframe', array('_secure' => true));
        }

        return parent::getOrderPlaceRedirectUrl();
    }

    /**
     * Call gateway by WS to do an instant payment
     *
     * @param  Mage_Sales_Model_Order_Payment $payment
     * @return Varien_Object
     */
    protected function _doInstantPayment($payment)
    {
        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $this->_getHelper()->log("Instant payment using WS for order #{$order->getIncrementId()}.");

        $requestId = '';

        try {
            $wsApi = $this->checkAndGetWsApi($storeId);

            $timestamp = time();

            // Common request generation.
            $commonRequest = new \Lyranetwork\Payzen\Model\Api\Ws\CommonRequest();
            $commonRequest->setPaymentSource('MOTO');
            $commonRequest->setSubmissionDate(new DateTime("@$timestamp"));

            // Amount in current order currency.
            $amount = $order->getGrandTotal();

            // Retrieve currency.
            $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($order->getOrderCurrencyCode());
            if ($currency == null) {
                // If currency is not supported, use base currency.
                $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($order->getBaseCurrencyCode());

                // ... and order total in base currency.
                $amount = $order->getBaseGrandTotal();
            }

            // Payment request generation.
            $paymentRequest = new \Lyranetwork\Payzen\Model\Api\Ws\PaymentRequest();
            $paymentRequest->setTransactionId(Lyranetwork_Payzen_Model_Api_Api::generateTransId($timestamp));
            $paymentRequest->setAmount($currency->convertAmountToInteger($amount));
            $paymentRequest->setCurrency($currency->getNum());

            $captureDelay = $this->getConfigData('capture_delay', $storeId); // Get submodule specific param.
            if (! is_numeric($captureDelay)) {
                // Get general param.
                $captureDelay = $this->_getHelper()->getCommonConfigData('capture_delay', $storeId);
            }

            if (is_numeric($captureDelay)) {
                $paymentRequest->setExpectedCaptureDate(
                    new DateTime('@' . strtotime("+$captureDelay days", $timestamp))
                );
            }

            $validationMode = $this->getConfigData('validation_mode', $storeId); // Get submodule specific param.
            if ($validationMode === '-1') {
                // Get general param.
                $validationMode = $this->_getHelper()->getCommonConfigData('validation_mode', $storeId);
            }

            if ($validationMode !== '') {
                $paymentRequest->setManualValidation($validationMode);
            }

            // Order request generation.
            $orderRequest = new \Lyranetwork\Payzen\Model\Api\Ws\OrderRequest();
            $orderRequest->setOrderId($order->getIncrementId());

            // Card request generation.
            $cardRequest = new \Lyranetwork\Payzen\Model\Api\Ws\CardRequest();
            $info = $this->getInfoInstance();
            $cardRequest->setNumber($info->getCcNumber());
            $cardRequest->setScheme($info->getCcType());
            $cardRequest->setCardSecurityCode($info->getCcCid());
            $cardRequest->setExpiryMonth($info->getCcExpMonth());
            $cardRequest->setExpiryYear($info->getCcExpYear());

            // Billing details generation.
            $billingDetailsRequest = new \Lyranetwork\Payzen\Model\Api\Ws\BillingDetailsRequest();
            $billingDetailsRequest->setReference($order->getCustomerId());

            if ($order->getBillingAddress()->getPrefix()) {
                $billingDetailsRequest->setTitle($order->getBillingAddress()->getPrefix());
            }

            $billingDetailsRequest->setFirstName($order->getBillingAddress()->getFirstname());
            $billingDetailsRequest->setLastName($order->getBillingAddress()->getLastname());
            $billingDetailsRequest->setPhoneNumber($order->getBillingAddress()->getTelephone());
            $billingDetailsRequest->setCellPhoneNumber($order->getBillingAddress()->getTelephone());
            $billingDetailsRequest->setEmail($order->getCustomerEmail());

            $address = $order->getBillingAddress()->getStreet(1) . ' ' . $order->getBillingAddress()->getStreet(2);
            $billingDetailsRequest->setAddress(trim($address));

            $billingDetailsRequest->setZipCode($order->getBillingAddress()->getPostcode());
            $billingDetailsRequest->setCity($order->getBillingAddress()->getCity());
            $billingDetailsRequest->setState($order->getBillingAddress()->getRegion());
            $billingDetailsRequest->setCountry($order->getBillingAddress()->getCountryId());

            // Language.
            $currentLang = substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2);
            if (Lyranetwork_Payzen_Model_Api_Api::isSupportedLanguage($currentLang)) {
                $language = $currentLang;
            } else {
                $language = $this->_getHelper()->getCommonConfigData('language', $storeId);
            }

            $billingDetailsRequest->setLanguage($language);

            // Shipping details generation.
            $shippingDetailsRequest = new \Lyranetwork\Payzen\Model\Api\Ws\ShippingDetailsRequest();

            $address = $order->getShippingAddress();
            if (is_object($address)) { // Deliverable order.
                $shippingDetailsRequest->setFirstName($address->getFirstname());
                $shippingDetailsRequest->setLastName($address->getLastname());
                $shippingDetailsRequest->setPhoneNumber($address->getTelephone());
                $shippingDetailsRequest->setAddress($address->getStreet(1));
                $shippingDetailsRequest->setAddress2($address->getStreet(2));
                $shippingDetailsRequest->setZipCode($address->getPostcode());
                $shippingDetailsRequest->setCity($address->getCity());
                $shippingDetailsRequest->setState($address->getRegion());
                $shippingDetailsRequest->setCountry($address->getCountryId());
            }

            // Extra details generation.
            $extraDetailsRequest = new \Lyranetwork\Payzen\Model\Api\Ws\ExtraDetailsRequest();
            $extraDetailsRequest->setIpAddress($this->_getHelper()->getIpAddress());

            // Customer request generation.
            $customerRequest = new \Lyranetwork\Payzen\Model\Api\Ws\CustomerRequest();
            $customerRequest->setBillingDetails($billingDetailsRequest);
            $customerRequest->setShippingDetails($shippingDetailsRequest);
            $customerRequest->setExtraDetails($extraDetailsRequest);

            // Create payment object generation.
            $createPayment = new \Lyranetwork\Payzen\Model\Api\Ws\CreatePayment();
            $createPayment->setCommonRequest($commonRequest);
            $createPayment->setPaymentRequest($paymentRequest);
            $createPayment->setOrderRequest($orderRequest);
            $createPayment->setCardRequest($cardRequest);
            $createPayment->setCustomerRequest($customerRequest);

            // Do createPayment WS call.
            $requestId = $wsApi->setHeaders();
            $createPaymentResponse = $wsApi->createPayment($createPayment);

            $wsApi->checkAuthenticity();
            $wsApi->checkResult(
                $createPaymentResponse->getCreatePaymentResult()->getCommonResponse(),
                array(
                    'INITIAL', 'NOT_CREATED', 'AUTHORISED', 'AUTHORISED_TO_VALIDATE',
                    'WAITING_AUTHORISATION', 'WAITING_AUTHORISATION_TO_VALIDATE'
                )
            );

            // Check operation type (0: debit, 1 refund).
            $transType = $createPaymentResponse->getCreatePaymentResult()->getPaymentResponse()->getOperationType();
            if ($transType != 0) {
                throw new Exception("Unexpected transaction type returned ($transType).");
            }

            // Update authorized amount.
            $payment->setAmountAuthorized($order->getTotalDue());
            $payment->setBaseAmountAuthorized($order->getBaseTotalDue());

            $wrapper = new Lyranetwork_Payzen_Model_Api_Ws_ResultWrapper(
                $createPaymentResponse->getCreatePaymentResult()->getCommonResponse(),
                $createPaymentResponse->getCreatePaymentResult()->getPaymentResponse(),
                $createPaymentResponse->getCreatePaymentResult()->getAuthorizationResponse(),
                $createPaymentResponse->getCreatePaymentResult()->getCardResponse(),
                $createPaymentResponse->getCreatePaymentResult()->getThreeDSResponse(),
                $createPaymentResponse->getCreatePaymentResult()->getFraudManagementResponse()
            );

            // Retrieve new order state and status.
            $stateObject = $this->_getPaymentHelper()->nextOrderState($wrapper, $order);
            $this->_getHelper()->log("Order #{$order->getIncrementId()}, new state: {$stateObject->getState()}, new status: {$stateObject->getStatus()}.");

            $order->setState($stateObject->getState(), $stateObject->getStatus(), $wrapper->getMessage());
            if ($stateObject->getState() === Mage_Sales_Model_Order::STATE_HOLDED) { // For magento 1.4.0.x
                $stateObject->setState($stateObject->getBeforeState());
                $stateObject->setStatus($stateObject->getBeforeStatus());
            }

            // Save gateway responses.
            $this->_getPaymentHelper()->updatePaymentInfo($order, $wrapper);

            // Try to create invoice.
            $this->_getPaymentHelper()->createInvoice($order);

            $stateObject->setIsNotified(true);
            return $stateObject;
        } catch(Lyranetwork_Payzen_Model_WsException $e) {
            $this->_getHelper()->log("[$requestId] {$e->getMessage()}", Zend_Log::WARN);

            $warn = $this->_getHelper()->__('Please correct this error to use PayZen web services.');
            $this->_getAdminSession()->addWarning($warn);
            $this->_getAdminSession()->addError($this->_getHelper()->__($e->getMessage()));
            Mage::throwException('');
        } catch(\SoapFault $f) {
            $this->_getHelper()->log(
                "[$requestId] SoapFault with code {$f->faultcode}: {$f->faultstring}.",
                Zend_Log::WARN
            );

            $warn = $this->_getHelper()->__('Please correct this error to use PayZen web services.');
            $this->_getAdminSession()->addWarning($warn);
            $this->_getAdminSession()->addError($f->faultstring);
            Mage::throwException('');
        } catch(\UnexpectedValueException $e) {
            $this->_getHelper()->log(
                "[$requestId] createPayment error with code {$e->getCode()}: {$e->getMessage()}.",
                Zend_Log::ERR
            );

            if ($e->getCode() === -1) {
                $this->_getAdminSession()->addError($this->_getHelper()->__('Authentication error ! '));
            } else {
                $this->_getAdminSession()->addError($e->getMessage());
            }

            Mage::throwException('');
        } catch (Exception $e) {
            $this->_getHelper()->log(
                "[$requestId] Exception with code {$e->getCode()}: {$e->getMessage()}",
                Zend_Log::ERR
            );

            $this->_getAdminSession()->addError($e->getMessage());
            Mage::throwException('');
        }
    }


    /**
     * Return true if iframe mode is enabled.
     *
     * @return string
     */
    public function isIframeMode()
    {
        return $this->getConfigData('card_info_mode') == 3;
    }

    /**
     * Check if the local card type selection option is choosen
     *
     * @return boolean
     */
    public function isLocalCcType()
    {
        return $this->getConfigData('card_info_mode') == 2;
    }
}
