<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\Method;

use Lyranetwork\Payzen\Model\Api\PayzenApi;

abstract class Payzen extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CART_MAX_NB_PRODUCTS = 85;

    protected $_infoBlockType = \Lyranetwork\Payzen\Block\Payment\Info::class;

    protected $_isplatform = true;
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

    protected $currencies = [];
    protected $needsCartData = false;

    /**
     *
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     *
     * @var \Lyranetwork\Payzen\Model\Api\PayzenRequest
     */
    protected $payzenRequest;

    /**
     *
     * @var \Lyranetwork\Payzen\Model\Api\PayzenResponse
     */
    protected $payzenResponse;

    /**
     *
     * @var \Magento\Sales\Model\Order\Payment\Transaction
     */
    protected $transaction;

    /**
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction
     */
    protected $transactionResource;

    /**
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     *
     * @var \Magento\Framework\App\Response\Http $
     */
    protected $redirect;

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Payment
     */
    protected $paymentHelper;

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     *
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     *
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $dirReader;

    /**
     *
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $dataObjectFactory;

    /**
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Lyranetwork\Payzen\Model\Api\PayzenRequest $payzenRequest
     * @param \Lyranetwork\Payzen\Model\Api\PayzenResponseFactory $payzenResponseFactory
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction $transactionResource
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\Response\Http $redirect
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     * @param \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Module\Dir\Reader $dirReader
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Lyranetwork\Payzen\Model\Api\PayzenRequestFactory $payzenRequestFactory,
        \Lyranetwork\Payzen\Model\Api\PayzenResponseFactory $payzenResponseFactory,
        \Magento\Sales\Model\Order\Payment\Transaction $transaction,
        \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction $transactionResource,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Response\Http $redirect,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Payment $paymentHelper,
        \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Module\Dir\Reader $dirReader,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->localeResolver = $localeResolver;
        $this->payzenRequest = $payzenRequestFactory->create();
        $this->payzenResponseFactory = $payzenResponseFactory;
        $this->transaction = $transaction;
        $this->transactionResource = $transactionResource;
        $this->urlBuilder = $urlBuilder;
        $this->redirect = $redirect;
        $this->dataHelper = $dataHelper;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->productMetadata = $productMetadata;
        $this->messageManager = $messageManager;
        $this->dirReader = $dirReader;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->authSession = $authSession;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     *
     * @param \Magento\Sales\Model\Order $order
     * @return <string:mixed> array of params as key=>value
     */
    public function getFormFields($order)
    {
        // Set order_id.
        $this->payzenRequest->set('order_id', $order->getIncrementId());

        // Amount in current order currency.
        $amount = $order->getGrandTotal();

        // Set currency.
        $currency = PayzenApi::findCurrencyByAlphaCode($order->getOrderCurrencyCode());
        if ($currency == null) {
            // If currency is not supported, use base currency.
            $currency = PayzenApi::findCurrencyByAlphaCode($order->getBaseCurrencyCode());

            // ... and order total in base currency
            $amount = $order->getBaseGrandTotal();
        }
        $this->payzenRequest->set('currency', $currency->getNum());

        // Set the amount to pay.
        $this->payzenRequest->set('amount', $currency->convertAmountToInteger($amount));

        // Contrib info.
        $cmsParam = $this->dataHelper->getCommonConfigData('cms_identifier') . '_'
            . $this->dataHelper->getCommonConfigData('plugin_version');
        $cmsVersion = $this->productMetadata->getVersion(); // Will return the Magento version.
        $this->payzenRequest->set('contrib', $cmsParam . '/' . $cmsVersion . '/' . PHP_VERSION);

        // Set config parameters.
        $configFields = [
            'site_id',
            'key_test',
            'key_prod',
            'ctx_mode',
            'sign_algo',
            'capture_delay',
            'validation_mode',
            'theme_config',
            'shop_name',
            'shop_url',
            'redirect_enabled',
            'redirect_success_timeout',
            'redirect_success_message',
            'redirect_error_timeout',
            'redirect_error_message',
            'return_mode'
        ];

        foreach ($configFields as $field) {
            $this->payzenRequest->set($field, $this->dataHelper->getCommonConfigData($field));
        }

        // Check if capture_delay and validation_mode are overriden in submodules.
        if (is_numeric($this->getConfigData('capture_delay'))) {
            $this->payzenRequest->set('capture_delay', $this->getConfigData('capture_delay'));
        }

        if ($this->getConfigData('validation_mode') !== '-1') {
            $this->payzenRequest->set('validation_mode', $this->getConfigData('validation_mode'));
        }

        // Set return url (build it and add store_id).
        $storeId = $this->dataHelper->isBackend() ? null : $order->getStore()->getId();
        $returnUrl = $this->dataHelper->getReturnUrl($storeId);

        $this->dataHelper->log('The complete return URL is ' . $returnUrl);
        $this->payzenRequest->set('url_return', $returnUrl);

        // Set the language code.
        $this->payzenRequest->set('language', $this->getPaymentLanguage());

        // Available_languages is given as csv by magento.
        $availableLanguages = explode(',', $this->dataHelper->getCommonConfigData('available_languages'));
        $availableLanguages = in_array('', $availableLanguages) ? '' : implode(';', $availableLanguages);
        $this->payzenRequest->set('available_languages', $availableLanguages);

        // Activate 3ds ?
        $threedsMpi = null;
        $threedsMinAmount = $this->dataHelper->getCommonConfigData('threeds_min_amount');
        if ($threedsMinAmount != '' && $order->getTotalDue() < $threedsMinAmount) {
            $threedsMpi = '2';
        }

        $this->payzenRequest->set('threeds_mpi', $threedsMpi);

        $this->payzenRequest->set('cust_email', $order->getCustomerEmail());
        $this->payzenRequest->set('cust_id', $order->getCustomerId());
        $this->payzenRequest->set('cust_title', $order->getBillingAddress()->getPrefix() ?
            $order->getBillingAddress()->getPrefix() : null);
        $this->payzenRequest->set('cust_first_name', $order->getBillingAddress()->getFirstname());
        $this->payzenRequest->set('cust_last_name', $order->getBillingAddress()->getLastname());
        $this->payzenRequest->set('cust_address', implode(' ', $order->getBillingAddress()->getStreet()));
        $this->payzenRequest->set('cust_zip', $order->getBillingAddress()->getPostcode());
        $this->payzenRequest->set('cust_city', $order->getBillingAddress()->getCity());
        $this->payzenRequest->set('cust_state', $order->getBillingAddress()->getRegion());
        $this->payzenRequest->set('cust_country', $order->getBillingAddress()->getCountryId());
        $this->payzenRequest->set('cust_phone', $order->getBillingAddress()->getTelephone());
        $this->payzenRequest->set('cust_cell_phone', $order->getBillingAddress()->getTelephone());

        $address = $order->getShippingAddress();
        if (is_object($address)) { // Shipping is supported.
            $this->payzenRequest->set('ship_to_first_name', $address->getFirstname());
            $this->payzenRequest->set('ship_to_last_name', $address->getLastname());
            $this->payzenRequest->set('ship_to_city', $address->getCity());
            $this->payzenRequest->set('ship_to_street', $address->getStreetLine(1));
            $this->payzenRequest->set('ship_to_street2', $address->getStreetLine(2));
            $this->payzenRequest->set('ship_to_state', $address->getRegion());
            $this->payzenRequest->set('ship_to_country', $address->getCountryId());
            $this->payzenRequest->set('ship_to_phone_num', $address->getTelephone());
            $this->payzenRequest->set('ship_to_zip', $address->getPostcode());
        }

        // Set method-specific parameters.
        $this->setExtraFields($order);

        $sendCartDetails = $this->dataHelper->getCommonConfigData('send_cart_detail') &&
            ($order->getTotalItemCount() <= self::CART_MAX_NB_PRODUCTS);

        // Add cart data.
        if ($sendCartDetails || $this->needsCartData /* Cart data are mandatory for the payment method. */) {
            $this->checkoutHelper->setCartData($order, $this->payzenRequest);
        }

        if ($this->sendOneyFields()) {
            // Set other data specific to FacilyPay Oney payment.
            $this->checkoutHelper->setOneyData($order, $this->payzenRequest);
        }

        $paramsToLog = $this->payzenRequest->getRequestFieldsArray(true);
        $this->dataHelper->log('Payment parameters: ' . json_encode($paramsToLog));

        return $this->payzenRequest->getRequestFieldsArray(false, false);
    }

    abstract protected function setExtraFields($order);

    protected function sendOneyFields()
    {
        return false;
    }

    /**
     * Retrieve information from payment configuration.
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if ($storeId === null && ! $this->getStore()) {
            $storeId = $this->dataHelper->getCheckoutStoreId();
        }

        return parent::getConfigData($field, $storeId);
    }

    /**
     * Get language to use on payment page.
     *
     * @return string
     */
    public function getPaymentLanguage()
    {
        $lang = strtolower(substr($this->localeResolver->getLocale(), 0, 2));
        if (! PayzenApi::isSupportedLanguage($lang)) {
            $lang = $this->dataHelper->getCommonConfigData('language');
        }

        return $lang;
    }

    /**
     * A flag to set that there will be redirect to third party after confirmation.
     *
     * @return bool
     */
    public function getOrderPlaceRedirectUrl()
    {
        return true;
    }

    /**
     * Return the payment gateway URL.
     *
     * @return string
     */
    public function getGatewayUrl()
    {
        return $this->dataHelper->getCommonConfigData('gateway_url');
    }

    /**
     * Assign data to info model instance.
     *
     * @param \Magento\Framework\DataObject $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        // Reset payment method specific data.
        $this->resetData();

        parent::assignData($data);
    }

    /**
     * Reset data of info model instance.
     *
     * @return $this
     */
    public function resetData()
    {
        $info = $this->getInfoInstance();

        $keys = [
            \Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION,
            \Lyranetwork\Payzen\Helper\Payment::CHOOZEO_OPTION,
            \Lyranetwork\Payzen\Helper\Payment::FULLCB_OPTION,
            \Lyranetwork\Payzen\Helper\Payment::ONEY_OPTION,
            \Lyranetwork\Payzen\Helper\Payment::IDENTIFIER
        ];

        foreach ($keys as $key) {
            $info->unsAdditionalInformation($key);
        }

        $info->setAdditionalData(null)
            ->setCcType(null)
            ->setCcLast4(null)
            ->setCcNumber(null)
            ->setCcCid(null)
            ->setCcExpMonth(null)
            ->setCcExpYear(null);

        return $this;
    }

    /**
     * Return an array of gateway payment specific data.
     *
     * @param \Magento\Framework\DataObject $data
     * @return array[string][string]
     */
    public function extractPaymentData(\Magento\Framework\DataObject $data)
    {
        if (is_array($data->getAdditionalData()) && ! empty($data->getAdditionalData())) {
            $dataObject = $this->dataObjectFactory->create();
            $dataObject->addData($data->getAdditionalData()); // Magento v >= 2.1
            return $dataObject;
        } else {
            return $data;
        }
    }

    /**
     * Attempt to accept a pending payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return bool
     */
    public function acceptPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        parent::acceptPayment($payment);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $this->dataHelper->log("Get payment information online for order #{$order->getIncrementId()}.");

        $requestId = '';

        try {
            $wsApi = $this->checkAndGetWsApi($storeId);

            $sid = false;

            // Retrieve transaction UUID.
            $uuid = $payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID);
            if (! $uuid) { // Retro compatibility.
                $legacyTransactionKeyRequest = new \Lyranetwork\Payzen\Model\Api\Ws\LegacyTransactionKeyRequest();
                $legacyTransactionKeyRequest->setTransactionId($payment->getCcTransId());
                $legacyTransactionKeyRequest->setSequenceNumber('1');
                $legacyTransactionKeyRequest->setCreationDate(new \DateTime($order->getCreatedAt()));

                $getPaymentUuid = new \Lyranetwork\Payzen\Model\Api\Ws\GetPaymentUuid();
                $getPaymentUuid->setLegacyTransactionKeyRequest($legacyTransactionKeyRequest);

                $requestId = $wsApi->setHeaders();
                $getPaymentUuidResponse = $wsApi->getPaymentUuid($getPaymentUuid);

                $wsApi->checkAuthenticity();
                $wsApi->checkResult($getPaymentUuidResponse->getLegacyTransactionKeyResult()->getCommonResponse());

                $uuid = $getPaymentUuidResponse->getLegacyTransactionKeyResult()
                    ->getPaymentResponse()
                    ->getTransactionUuid();

                // Retrieve JSESSIONID created for getPaymentUuid call.
                $sid = $wsApi->getJsessionId();
            }

            // Common $queryRequest object to use in all operations.
            $queryRequest = new \Lyranetwork\Payzen\Model\Api\Ws\QueryRequest();
            $queryRequest->setUuid($uuid);

            $getPaymentDetails = new \Lyranetwork\Payzen\Model\Api\Ws\GetPaymentDetails($queryRequest);
            $getPaymentDetails->setQueryRequest($queryRequest);

            $requestId = $wsApi->setHeaders();

            // Set JSESSIONID if ws getPaymentUuid is called.
            if ($sid) {
                $wsApi->setJsessionId($sid);
            }

            $getPaymentDetailsResponse = $wsApi->getPaymentDetails($getPaymentDetails);

            $wsApi->checkAuthenticity();
            $wsApi->checkResult(
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getCommonResponse(),
                [
                    'INITIAL', 'WAITING_AUTHORISATION', 'WAITING_AUTHORISATION_TO_VALIDATE', 'UNDER_VERIFICATION',
                    'WAITING_FOR_PAYMENT', 'AUTHORISED', 'AUTHORISED_TO_VALIDATE', 'CAPTURED', 'CAPTURE_FAILED',
                    'ACCEPTED'
                ] // Pending or accepted payment.
            );

            // Check operation type (0: debit, 1 refund, 5: verification).
            $transType = $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getPaymentResponse()->getOperationType();
            if (($transType !== 0) && ($transType !== 5)) {
                throw new \Exception("Unexpected transaction type returned ($transType).");
            }

            $this->dataHelper->log("Updating payment information for accepted order #{$order->getIncrementId()}.");

            // Payment is accepted by merchant.
            $payment->setIsFraudDetected(false);

            $wrapper = new \Lyranetwork\Payzen\Model\Api\Ws\ResultWrapper(
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getCommonResponse(),
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getPaymentResponse(),
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getAuthorizationResponse(),
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getCardResponse(),
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getThreeDSResponse(),
                $getPaymentDetailsResponse->getGetPaymentDetailsResult()->getFraudManagementResponse()
            );

            // Load API response.
            $response = $this->payzenResponseFactory->create(
                [
                    'params' => $wrapper->getResponseParams(),
                    'ctx_mode' => $this->dataHelper->getCommonConfigData('ctx_mode', $storeId),
                    'key_test' => $this->dataHelper->getCommonConfigData('key_test', $storeId),
                    'key_prod' => $this->dataHelper->getCommonConfigData('key_prod', $storeId)
                ]
            );
            $stateObject = $this->paymentHelper->nextOrderState($order, $response, true);

            $this->dataHelper->log("Order #{$order->getIncrementId()}, new state : {$stateObject->getState()}, new status : {$stateObject->getStatus()}.");
            $order->setState($stateObject->getState())
                ->setStatus($stateObject->getStatus())
                ->addStatusHistoryComment(__('The payment has been accepted.'));

            // Try to create invoice.
            $this->paymentHelper->createInvoice($order);

            $order->save();
            $this->messageManager->addSuccess(__('The payment has been accepted.'));

            $redirectUrl = $this->urlBuilder->getUrl(
                'sales/order/view',
                [
                    'order_id' => $order->getId()
                ]
            );

            $this->redirect->setRedirect($redirectUrl)->sendResponse();
            exit;
        } catch(\Lyranetwork\Payzen\Model\WsException $e) {
            $this->dataHelper->log(
                "[$requestId] {$e->getMessage()}",
                \Psr\Log\LogLevel::WARNING
            );

            $this->messageManager->addWarning(__('Please fix this error to use PayZen web services.'));
            $this->messageManager->addError(__($e->getMessage()));

            return true;
        } catch(\SoapFault $f) {
            $this->dataHelper->log(
                "[$requestId] SoapFault with code {$f->faultcode}: {$f->faultstring}.",
                \Psr\Log\LogLevel::WARNING
            );

            $this->messageManager->addWarning(__('Please fix this error to use PayZen web services.'));
            $this->messageManager->addError($f->faultstring);

            return true;
        } catch(\UnexpectedValueException $e) {
            $this->dataHelper->log(
                "[$requestId] getPaymentDetails error with code {$e->getCode()}: {$e->getMessage()}.",
                \Psr\Log\LogLevel::ERROR
            );

            if ($e->getCode() === -1) {
                throw new \Exception(__('Authentication error !'));
            } elseif ($e->getCode() === 1) {
                // Merchant does not subscribe to WS option, accept payment offline.
                return true;
            } else {
                throw new \Exception($e->getMessage());
            }
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "[$requestId] Exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            throw $e;
        }
    }

    /**
     * Attempt to deny a pending payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return bool
     */
    public function denyPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        parent::denyPayment($payment);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $this->dataHelper->log("Cancel payment online for order #{$order->getIncrementId()}.");

        $requestId = '';

        try {
            $wsApi = $this->checkAndGetWsApi($storeId);

            $sid = false;

            // Retrieve transaction UUID.
            $uuid = $payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID);
            if (! $uuid) { // Retro compatibility.
                $legacyTransactionKeyRequest = new \Lyranetwork\Payzen\Model\Api\Ws\LegacyTransactionKeyRequest();
                $legacyTransactionKeyRequest->setTransactionId($payment->getCcTransId());
                $legacyTransactionKeyRequest->setSequenceNumber('1');
                $legacyTransactionKeyRequest->setCreationDate(new \DateTime($order->getCreatedAt()));

                $getPaymentUuid = new \Lyranetwork\Payzen\Model\Api\Ws\GetPaymentUuid();
                $getPaymentUuid->setLegacyTransactionKeyRequest($legacyTransactionKeyRequest);

                $requestId = $wsApi->setHeaders();
                $getPaymentUuidResponse = $wsApi->getPaymentUuid($getPaymentUuid);

                $wsApi->checkAuthenticity();
                $wsApi->checkResult($getPaymentUuidResponse->getLegacyTransactionKeyResult()->getCommonResponse());

                $uuid = $getPaymentUuidResponse->getLegacyTransactionKeyResult()
                    ->getPaymentResponse()
                    ->getTransactionUuid();

                // Retrieve JSESSIONID created for getPaymentUuid call.
                $sid = $wsApi->getJsessionId();
            }

            // Common $queryRequest object to use in all operations.
            $queryRequest = new \Lyranetwork\Payzen\Model\Api\Ws\QueryRequest();
            $queryRequest->setUuid($uuid);

            $cancelPayment = new \Lyranetwork\Payzen\Model\Api\Ws\CancelPayment();
            $cancelPayment->setCommonRequest(new \Lyranetwork\Payzen\Model\Api\Ws\CommonRequest());
            $cancelPayment->setQueryRequest($queryRequest);

            $requestId = $wsApi->setHeaders();

            // Set JSESSIONID if ws getPaymentUuid is called.
            if ($sid) {
                $wsApi->setJsessionId($sid);
            }

            $cancelPaymentResponse = $wsApi->cancelPayment($cancelPayment);

            $wsApi->checkAuthenticity();
            $wsApi->checkResult(
                $cancelPaymentResponse->getCancelPaymentResult()->getCommonResponse(),
                ['CANCELLED']
            );

            $this->dataHelper->log("Payment cancelled successfully online for order #{$order->getIncrementId()}.");

            $transactionId = $payment->getCcTransId() . '-1';
            $additionalInfo = [];

            $txn = $this->transactionResource->loadObjectByTxnId(
                $this->transaction,
                $order->getId(),
                $payment->getId(),
                $transactionId
            );

            if ($txn && $txn->getId()) {
               $additionalInfo = $txn->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);
            }

            // New transaction status.
            $additionalInfo['Transaction Status'] = 'CANCELLED';

            $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID;
            $this->paymentHelper->addTransaction($payment, $transactionType, $transactionId, $additionalInfo);

            return true; // Let Magento cancel order.
        } catch(\Lyranetwork\Payzen\Model\WsException $e) {
            $this->dataHelper->log(
                "[$requestId] {$e->getMessage()}",
                \Psr\Log\LogLevel::WARNING
            );

            $this->messageManager->addWarning(__('Please fix this error to use PayZen web services.'));
            $this->messageManager->addError(__($e->getMessage()));

            return true;
        } catch(\SoapFault $f) {
            $this->dataHelper->log(
                "[$requestId] SoapFault with code {$f->faultcode}: {$f->faultstring}.",
                \Psr\Log\LogLevel::WARNING
            );

            $this->messageManager->addWarning(__('Please fix this error to use PayZen web services.'));
            $this->messageManager->addError($f->faultstring);

            return true;
        } catch(\UnexpectedValueException $e) {
            $this->dataHelper->log(
                "[$requestId] cancelPayment error with code {$e->getCode()}: {$e->getMessage()}.",
                \Psr\Log\LogLevel::ERROR
            );

            if ($e->getCode() === -1) {
                throw new \Exception(__('Authentication error !'));
            } elseif ($e->getCode() === 1) {
                // Merchant does not subscribe to WS option, cancel payment offline.
                $notice = __('You are not authorized to do this action online. Please, do not forget to update payment in PayZen Back Office.');
                $this->messageManager->addNotice($notice);

                return true;
            } else {
                throw new \Exception($e->getMessage());
            }
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "[$requestId] Exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            throw $e;
        }
    }

    /**
     * Attempt to validate a pending payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return bool
     */
    public function validatePayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $this->dataHelper->log("Validate payment online for order #{$order->getIncrementId()}.");

        $requestId = '';

        try {
            $wsApi = $this->checkAndGetWsApi($storeId);

            $sid = false;

            // Get choosen payment option if any.
            $option = @unserialize($payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION));
            $multi = (stripos($payment->getMethod(), 'payzen_multi') === 0) && is_array($option) && !empty($option);
            $count = $multi ? (int) $option['count'] : 1;

            // Retrieve saved transaction UUID.
            $savedUuid = $payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID);

            for ($i = 1; $i <= $count; $i++) {
                if ($i == 1 && $savedUuid) {
                    $uuid = $savedUuid;
                } else {
                    $legacyTransactionKeyRequest = new \Lyranetwork\Payzen\Model\Api\Ws\LegacyTransactionKeyRequest();
                    $legacyTransactionKeyRequest->setTransactionId($payment->getCcTransId());
                    $legacyTransactionKeyRequest->setSequenceNumber($i);
                    $legacyTransactionKeyRequest->setCreationDate(new \DateTime($order->getCreatedAt()));

                    $getPaymentUuid = new \Lyranetwork\Payzen\Model\Api\Ws\GetPaymentUuid();
                    $getPaymentUuid->setLegacyTransactionKeyRequest($legacyTransactionKeyRequest);

                    $requestId = $wsApi->setHeaders();
                    $getPaymentUuidResponse = $wsApi->getPaymentUuid($getPaymentUuid);

                    $wsApi->checkAuthenticity();
                    $wsApi->checkResult($getPaymentUuidResponse->getLegacyTransactionKeyResult()->getCommonResponse());

                    $uuid = $getPaymentUuidResponse->getLegacyTransactionKeyResult()
                        ->getPaymentResponse()
                        ->getTransactionUuid();
                }

                // Common $queryRequest object to use in all operations.
                $queryRequest = new \Lyranetwork\Payzen\Model\Api\Ws\QueryRequest();
                $queryRequest->setUuid($uuid);

                $validatePayment = new \Lyranetwork\Payzen\Model\Api\Ws\ValidatePayment();
                $validatePayment->setCommonRequest(new \Lyranetwork\Payzen\Model\Api\Ws\CommonRequest());
                $validatePayment->setQueryRequest($queryRequest);

                $requestId = $wsApi->setHeaders();

                // Set JSESSIONID if ws getPaymentUuid is called.
                if ($sid) {
                    $wsApi->setJsessionId($sid);
                }

                $validatePaymentResponse = $wsApi->validatePayment($validatePayment);

                $wsApi->checkAuthenticity();
                $wsApi->checkResult(
                    $validatePaymentResponse->getValidatePaymentResult()->getCommonResponse(),
                    ['WAITING_AUTHORISATION', 'AUTHORISED']
                );

                $wrapper = new \Lyranetwork\Payzen\Model\Api\Ws\ResultWrapper($validatePaymentResponse->getValidatePaymentResult()->getCommonResponse());

                // Load API response.
                $response = $this->payzenResponseFactory->create(
                    [
                        'params' => $wrapper->getResponseParams(),
                        'ctx_mode' => $this->dataHelper->getCommonConfigData('ctx_mode', $storeId),
                        'key_test' => $this->dataHelper->getCommonConfigData('key_test', $storeId),
                        'key_prod' => $this->dataHelper->getCommonConfigData('key_prod', $storeId)
                    ]
                );

                $transId = $order->getPayment()->getCcTransId() . '-'. $i;

                if ($i === 1) { // Single payment or first transaction for payment in installments.
                    $stateObject = $this->paymentHelper->nextOrderState($order, $response, true);

                    $this->dataHelper->log("Order #{$order->getIncrementId()}, new state : {$stateObject->getState()}, new status : {$stateObject->getStatus()}.");
                    $order->setState($stateObject->getState())
                        ->setStatus($stateObject->getStatus());
                }

                $order->addStatusHistoryComment(__('Transaction %1 has been validated.', $transId));

                // Update transaction status.
                $txn = $this->transactionResource->loadObjectByTxnId(
                    $this->transaction,
                    $order->getId(),
                    $order->getPayment()->getId(),
                    $transId
                );

                if ($txn && $txn->getId()) {
                    $data = $txn->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);
                    $data['Transaction Status'] = $response->getTransStatus();

                    $txn->setAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $data);
                    $txn->save();
                }
            }

            $this->dataHelper->log("Updating payment information for validated order #{$order->getIncrementId()}.");

            // Try to create invoice.
            $this->paymentHelper->createInvoice($order);

            $order->save();
            $this->messageManager->addSuccess(__('Payment validated successfully.'));
        } catch(\Lyranetwork\Payzen\Model\WsException $e) {
            $this->dataHelper->log(
                "[$requestId] {$e->getMessage()}",
                \Psr\Log\LogLevel::WARNING
            );

            $this->messageManager->addWarning(__('Please fix this error to use PayZen web services.'));
            $this->messageManager->addError(__($e->getMessage()));
        } catch(\SoapFault $f) {
            $this->dataHelper->log(
                "[$requestId] SoapFault with code {$f->faultcode}: {$f->faultstring}.",
                \Psr\Log\LogLevel::WARNING
            );

            $this->messageManager->addWarning(__('Please fix this error to use PayZen web services.'));
            $this->messageManager->addError($f->faultstring);
        } catch(\UnexpectedValueException $e) {
            $this->dataHelper->log(
                "[$requestId] validatePayment error with code {$e->getCode()}: {$e->getMessage()}.",
                \Psr\Log\LogLevel::ERROR
            );

            if ($e->getCode() === -1) {
                throw new \Exception(__('Authentication error !'));
            } elseif ($e->getCode() === 1) {
                // Merchant does not subscribe to WS option, validate payment offline.
                $notice = __('You are not authorized to do this action online. Please, do not forget to update payment in PayZen Back Office.');
                $this->messageManager->addNotice($notice);
            } else {
                $this->messageManager->addError($e->getMessage());
            }
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "[$requestId] Exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            $this->messageManager->addError($e->getMessage());
        }
    }

    /**
     * Method that will be executed instead of authorize or capture if flag isInitializeNeeded set to true.
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->dataHelper->log("Initialize payment called with action $paymentAction.");

        if ($paymentAction !== \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE) {
            return;
        }

        // Avoid sending order by e-mail before redirection.
        $order = $this->getInfoInstance()->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);

        return $this;
    }

    /**
     * Check method for processing with base currency.
     *
     * @param string $baseCurrencyCode
     * @return bool
     */
    public function canUseForCurrency($baseCurrencyCode)
    {
        // Check selected currency support.
        $currencyCode = '';
        $quote = $this->dataHelper->getCheckoutQuote();
        if ($quote && $quote->getId()) {
            $currencyCode = $quote->getQuoteCurrencyCode();

            // If submodule support specific currencies, check quote currency over them.
            if (is_array($this->currencies) && ! empty($this->currencies)) {
                return in_array($currencyCode, $this->currencies);
            }

            $currency = PayzenApi::findCurrencyByAlphaCode($currencyCode);
            if ($currency) {
                return true;
            }
        }

        // Check base currency support.
        $currency = PayzenApi::findCurrencyByAlphaCode($baseCurrencyCode);
        if ($currency) {
            return true;
        }

        $this->dataHelper->log("Could not find numeric codes for selected ($currencyCode)" .
            " and base ($baseCurrencyCode) currencies.");
        return false;
    }

    /**
     * Return true if the method can be used at this time.
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (! parent::isAvailable($quote)) {
            return false;
        }

        $amount = $quote ? $quote->getBaseGrandTotal() : null;
        if (! $amount) {
            return true;
        }

        $configOptions = $this->dataHelper->unserialize($this->getConfigData('custgroup_amount_restriction'));
        if (! is_array($configOptions) || empty($configOptions)) {
            return true;
        }

        $group = $quote && $quote->getCustomer() ? $quote->getCustomer()->getGroupId() : null;

        $allMinAmount = null;
        $allMaxAmount = null;
        $minAmount = null;
        $maxAmount = null;
        foreach ($configOptions as $value) {
            if (empty($value)) {
                continue;
            }

            if ($value['code'] === 'all') {
                $allMinAmount = $value['amount_min'];
                $allMaxAmount = $value['amount_max'];
            } elseif ($value['code'] === $group) {
                $minAmount = $value['amount_min'];
                $maxAmount = $value['amount_max'];
            }
        }

        if (! $minAmount) {
            $minAmount = $allMinAmount;
        }

        if (! $maxAmount) {
            $maxAmount = $allMaxAmount;
        }

        if (($minAmount && ($amount < $minAmount)) || ($maxAmount && ($amount > $maxAmount))) {
            // Module will not be available.
            return false;
        }

        return true;
    }

    /**
     * Refund money.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return Lyranetwork\Payzen\Model\Method\Payzen
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $requestId = '';

        $this->dataHelper->log("Start refund of {$amount} {$order->getOrderCurrencyCode()} for order " .
             "#{$order->getIncrementId()} with {$this->_code} payment method.");

        try {
            $wsApi = $this->checkAndGetWsApi($storeId);

            // Get currency.
            $currency = PayzenApi::findCurrencyByAlphaCode($order->getOrderCurrencyCode());

            $sid = false;

            // Retrieve transaction UUID.
            $uuid = $payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID);
            if (! $uuid) { // Retro compatibility.
                $legacyTransactionKeyRequest = new \Lyranetwork\Payzen\Model\Api\Ws\LegacyTransactionKeyRequest();
                $legacyTransactionKeyRequest->setTransactionId($payment->getCcTransId());
                $legacyTransactionKeyRequest->setSequenceNumber('1'); // Only single payments can be refund.
                $legacyTransactionKeyRequest->setCreationDate(new \DateTime($order->getCreatedAt()));

                $getPaymentUuid = new \Lyranetwork\Payzen\Model\Api\Ws\GetPaymentUuid();
                $getPaymentUuid->setLegacyTransactionKeyRequest($legacyTransactionKeyRequest);

                $requestId = $wsApi->setHeaders();
                $getPaymentUuidResponse = $wsApi->getPaymentUuid($getPaymentUuid);

                $wsApi->checkAuthenticity();
                $wsApi->checkResult($getPaymentUuidResponse->getLegacyTransactionKeyResult()->getCommonResponse());

                $uuid = $getPaymentUuidResponse->getLegacyTransactionKeyResult()
                    ->getPaymentResponse()
                    ->getTransactionUuid();

                // Retrieve JSESSIONID created for getPaymentUuid call.
                $sid = $wsApi->getJsessionId();
            }

            // Common $queryRequest object to use in all operations.
            $queryRequest = new \Lyranetwork\Payzen\Model\Api\Ws\QueryRequest();
            $queryRequest->setUuid($uuid);

            $getPaymentDetails = new \Lyranetwork\Payzen\Model\Api\Ws\GetPaymentDetails();
            $getPaymentDetails->setQueryRequest($queryRequest);

            $requestId = $wsApi->setHeaders();
            if ($sid) { // Set JSESSIONID if ws getPaymentUuid is called.
                $wsApi->setJsessionId($sid);
            }

            $getPaymentDetailsResponse = $wsApi->getPaymentDetails($getPaymentDetails);

            $wsApi->checkAuthenticity();
            $wsApi->checkResult($getPaymentDetailsResponse->getGetPaymentDetailsResult()->getCommonResponse());

            // Retrieve JSESSIONID created for getPaymentDetails call.
            if (! $sid) {
                $sid = $wsApi->getJsessionId();
            }

            $transStatus = $getPaymentDetailsResponse->getGetPaymentDetailsResult()
                ->getCommonResponse()
                ->getTransactionStatusLabel();
            $amountInCents = $currency->convertAmountToInteger($amount);

            // Common request generation.
            $commonRequest = new \Lyranetwork\Payzen\Model\Api\Ws\CommonRequest();
            $commentText = 'Magento user: ' . $this->authSession->getUser()->getUsername();
            $commentText .= '; IP address: ' . $this->dataHelper->getIpAddress();
            foreach ($payment->getCreditmemo()->getComments() as $comment) {
                $commentText .= '; ' . $comment->getComment();
            }

            $commonRequest->setComment($commentText);

            $requestId = $wsApi->setHeaders();
            $wsApi->setJsessionId($sid); // Set JSESSIONID for the last ws call.

            if ($transStatus === 'CAPTURED') { // Transaction captured, we can do refund.
                $timestamp = time();

                $paymentRequest = new \Lyranetwork\Payzen\Model\Api\Ws\PaymentRequest();
                $paymentRequest->setTransactionId(PayzenApi::generateTransId($timestamp));
                $paymentRequest->setAmount($amountInCents);
                $paymentRequest->setCurrency($currency->getNum());

                $captureDelay = $this->getConfigData('capture_delay', $storeId); // Get submodule specific param.
                if (! is_numeric($captureDelay)) {
                    // Get general param.
                    $captureDelay = $this->dataHelper->getCommonConfigData('capture_delay', $storeId);
                }

                if (is_numeric($captureDelay)) {
                    $paymentRequest->setExpectedCaptureDate(new \DateTime('@' . strtotime("+$captureDelay days", $timestamp)));
                }

                $validationMode = $this->getConfigData('validation_mode', $storeId); // Get submodule specific param.
                if ($validationMode === '-1') {
                    // Get general param.
                    $validationMode = $this->dataHelper->getCommonConfigData('validation_mode', $storeId);
                }

                if ($validationMode !== '') {
                    $paymentRequest->setManualValidation($validationMode);
                }

                $refundPayment = new \Lyranetwork\Payzen\Model\Api\Ws\RefundPayment();
                $refundPayment->setCommonRequest($commonRequest);
                $refundPayment->setPaymentRequest($paymentRequest);
                $refundPayment->setQueryRequest($queryRequest);
                $refurndPaymentResponse = $wsApi->refundPayment($refundPayment);

                $wsApi->checkAuthenticity();
                $wsApi->checkResult(
                    $refurndPaymentResponse->getRefundPaymentResult()->getCommonResponse(),
                    [
                        'INITIAL',
                        'AUTHORISED',
                        'AUTHORISED_TO_VALIDATE',
                        'WAITING_AUTHORISATION',
                        'WAITING_AUTHORISATION_TO_VALIDATE',
                        'CAPTURED',
                        'UNDER_VERIFICATION'
                    ]
                );

                // Check operation type (0: debit, 1 refund).
                $transType = $refurndPaymentResponse->getRefundPaymentResult()
                    ->getPaymentResponse()
                    ->getOperationType();
                if ($transType != 1) {
                    throw new \Exception("Unexpected transaction type returned ($transType).");
                }

                // Create refund transaction in Magento.
                $this->createRefundTransaction(
                    $payment,
                    $refurndPaymentResponse->getRefundPaymentResult()->getCommonResponse(),
                    $refurndPaymentResponse->getRefundPaymentResult()->getPaymentResponse(),
                    $refurndPaymentResponse->getRefundPaymentResult()->getCardResponse()
                );

                $this->dataHelper->log("Online money refund for order #{$order->getIncrementId()} is successful.");
            } else {
                $transAmount = $getPaymentDetailsResponse->getGetPaymentDetailsResult()
                    ->getPaymentResponse()
                    ->getAmount();
                if ($amountInCents >= $transAmount) { // Transaction cancel.
                    $cancelPayment = new \Lyranetwork\Payzen\Model\Api\Ws\CancelPayment();
                    $cancelPayment->setCommonRequest($commonRequest);
                    $cancelPayment->setQueryRequest($queryRequest);

                    $cancelPaymentResponse = $wsApi->cancelPayment($cancelPayment);

                    $wsApi->checkAuthenticity();
                    $wsApi->checkResult(
                        $cancelPaymentResponse->getCancelPaymentResult()->getCommonResponse(),
                        [
                            'CANCELLED'
                        ]
                    );

                    $order->cancel();
                    $this->dataHelper->log("Online payment cancel for order #{$order->getIncrementId()} is successful.");
                } else { // Partial transaction refund, call updatePayment WS.
                    $paymentRequest = new \Lyranetwork\Payzen\Model\Api\Ws\PaymentRequest();
                    $paymentRequest->setAmount($transAmount - $amountInCents);
                    $paymentRequest->setCurrency($currency->getNum());

                    $updatePayment = new \Lyranetwork\Payzen\Model\Api\Ws\UpdatePayment();
                    $updatePayment->setCommonRequest($commonRequest);
                    $updatePayment->setQueryRequest($queryRequest);
                    $updatePayment->setPaymentRequest($paymentRequest);

                    $updatePaymentResponse = $wsApi->updatePayment($updatePayment);

                    $wsApi->checkAuthenticity();
                    $wsApi->checkResult(
                        $updatePaymentResponse->getUpdatePaymentResult()->getCommonResponse(),
                        [
                            'AUTHORISED',
                            'AUTHORISED_TO_VALIDATE',
                            'WAITING_AUTHORISATION',
                            'WAITING_AUTHORISATION_TO_VALIDATE'
                        ]
                    );
                    $this->dataHelper->log("Online payment update for order #{$order->getIncrementId()} is successful.");
                }
            }
        } catch (\Lyranetwork\Payzen\Model\WsException $e) {
            $this->dataHelper->log("[$requestId] {$e->getMessage()}", \Psr\Log\LogLevel::WARNING);

            $this->messageManager->addError($e->getMessage());
            $this->messageManager->addWarning('Please fix error to refund payments through PayZen. If you want to refund order in Magento, use the &laquo; Refund Offline &raquo; button.');
            throw new \Exception($e->getMessage());
        } catch (\SoapFault $f) {
            $this->dataHelper->log(
                "[$requestId] SoapFault with code {$f->faultcode}: {$f->faultstring}.",
                \Psr\Log\LogLevel::WARNING
            );

            $this->messageManager->addError($f->faultstring);
            $this->messageManager->addWarning('Please fix error to refund payments through PayZen. If you want to refund order in Magento, use the &laquo; Refund Offline &raquo; button.');
            throw new \Exception($f->faultstring);
        } catch(\UnexpectedValueException $e) {
            $this->dataHelper->log("[$requestId] refund error with code {$e->getCode()}: {$e->getMessage()}.", \Psr\Log\LogLevel::ERROR);

            if ($e->getCode() === -1) {
                throw new \Exception(__('Authentication error !'));
            } elseif ($e->getCode() === 1) {
                // Merchant does not subscribe to WS option, refund payment offline.
                $notice = __('You are not authorized to do this action online. Please, do not forget to update payment in PayZen Back Office.');
                $this->messageManager->addNotice($notice);
                // Magento will do an offline refund.
            } elseif ($e->getCode() === 83) {
                throw new \Exception(__('Chargebacks cannot be refunded.'));
            } else {
                throw new \Exception($e->getMessage());
            }
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "[$requestId] Exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            $message = __('Refund error')->render() . ': ' . $e->getMessage();
            $this->messageManager->addError($message);
            throw $e;
        }

        $order->save();
        return $this;
    }

    private function createRefundTransaction($payment, $commonResponse, $paymentResponse, $cardResponse)
    {
        $currency = PayzenApi::findCurrencyByNumCode($paymentResponse->getCurrency());

        // Save transaction details to sales_payment_transaction.
        $transactionId = $paymentResponse->getTransactionId() . '-' . $paymentResponse->getSequenceNumber();

        $expiry = '';
        if ($cardResponse->getExpiryMonth() && $cardResponse->getExpiryYear()) {
            $expiry = str_pad($cardResponse->getExpiryMonth(), 2, '0', STR_PAD_LEFT) . ' / ' .
                 $cardResponse->getExpiryYear();
        }

        // Save paid amount.
        $currency = PayzenApi::findCurrencyByNumCode($paymentResponse->getCurrency());
        $amount = round($currency->convertAmountToFloat($paymentResponse->getAmount()), $currency->getDecimals());

        $amountDetail = $amount . ' ' . $currency->getAlpha3();

        if ($paymentResponse->getEffectiveCurrency() &&
             ($paymentResponse->getCurrency() !== $paymentResponse->getEffectiveCurrency())) {
            $effectiveCurrency = PayzenApi::findCurrencyByNumCode($paymentResponse->getEffectiveCurrency());

            $effectiveAmount = round(
                $effectiveCurrency->convertAmountToFloat($paymentResponse->getEffectiveAmount()),
                $effectiveCurrency->getDecimals()
            );

            $amountDetail = $effectiveAmount . ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
        }

        $additionalInfo = [
            'Transaction Type' => 'CREDIT',
            'Amount' => $amountDetail,
            'Transaction ID' => $transactionId,
            'Transaction UUID' => $paymentResponse->getTransactionUuid(),
            'Transaction Status' => $commonResponse->getTransactionStatusLabel(),
            'Means of payment' => $cardResponse->getBrand(),
            'Card Number' => $cardResponse->getNumber(),
            'Expiration Date' => $expiry
        ];

        $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND;
        $this->paymentHelper->addTransaction($payment, $transactionType, $transactionId, $additionalInfo);
    }

    protected function checkAndGetWsApi($storeId)
    {
        $this->dataHelper->checkWsRequirements();

        // Headers generation.
        $shopId = $this->dataHelper->getCommonConfigData('site_id', $storeId);
        $mode = $this->dataHelper->getCommonConfigData('ctx_mode', $storeId);
        $keyTest = $this->dataHelper->getCommonConfigData('key_test', $storeId);
        $keyProd = $this->dataHelper->getCommonConfigData('key_prod', $storeId);

        // Load specific configuration file for WS.
        $options = parse_ini_file($this->dirReader->getModuleDir('etc', 'Lyranetwork_Payzen') . '/ws.ini') ?: [];

        if (! empty($options)) {
            if (! $options['proxy.enabled']) {
                unset(
                    $options['proxy_host'],
                    $options['proxy_port'],
                    $options['proxy_login'],
                    $options['proxy_password']
                );
            }

            unset($options['proxy.enabled']);
        }

        $url = $this->dataHelper->getCommonConfigData('wsdl_url', $storeId);

        $wsApi = new \Lyranetwork\Payzen\Model\Api\Ws\WsApi($url, $options);
        $wsApi->init($shopId, $mode, $keyTest, $keyProd);

        return $wsApi;
    }
}
