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
use Lyranetwork\Payzen\Model\Api\PayzenRest;

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
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Lyranetwork\Payzen\Model\Api\PayzenRequest
     */
    protected $payzenRequest;

    /**
     * @var \Lyranetwork\Payzen\Model\Api\PayzenResponse
     */
    protected $payzenResponse;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction
     */
    protected $transaction;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction
     */
    protected $transactionResource;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\Response\Http $
     */
    protected $redirect;

    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Lyranetwork\Payzen\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * @var \Lyranetwork\Payzen\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * @var \Lyranetwork\Payzen\Helper\Rest
     */
    protected $restHelper;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $dirReader;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
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
     * @param \Lyranetwork\Payzen\Helper\Rest $restHelper
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
        \Lyranetwork\Payzen\Helper\Rest $restHelper,
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
        $this->restHelper = $restHelper;
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

        // Activate 3ds?
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
        $this->payzenRequest->set('cust_state', $order->getBillingAddress()->getRegionCode());
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
            $this->payzenRequest->set('ship_to_state', $address->getRegionCode());
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
            \Lyranetwork\Payzen\Helper\Payment::OTHER_OPTION,
            \Lyranetwork\Payzen\Helper\Payment::CHOOZEO_OPTION,
            \Lyranetwork\Payzen\Helper\Payment::FULLCB_OPTION,
            \Lyranetwork\Payzen\Helper\Payment::ONEY_OPTION,
            \Lyranetwork\Payzen\Helper\Payment::IDENTIFIER,
            \Lyranetwork\Payzen\Helper\Payment::SEPA_IDENTIFIER
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

        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        // Client has not configured private key in module backend, let Magento accept order offline.
        if (! $this->restHelper->getPrivateKey($storeId)) {
            $this->dataHelper->log("Cannot get online payment information for order #{$order->getIncrementId()}: private key is not configured, let Magento accepts the payment.");

            return true;
        }

        $this->dataHelper->log("Get payment information online for order #{$order->getIncrementId()}.");

        try {
            // Retrieve transaction UUID.
            $uuid = $payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID);
            if (! $uuid) { // Retro compatibility.
                $data = $this->getPaymentDetails($order, false);
                $getPaymentDetails['answer'] = reset($data);
                $getPaymentDetails['status'] = 'SUCCESS';
            } else {
                $requestData = ['uuid' => $uuid];

                // Perform our request.
                $client = new PayzenRest(
                    $this->dataHelper->getCommonConfigData('rest_url', $storeId),
                    $this->dataHelper->getCommonConfigData('site_id', $storeId),
                    $this->restHelper->getPrivateKey($storeId)
                );

                $getPaymentDetails = $client->post('V4/Transaction/Get', json_encode($requestData));
            }

            // Pending or accepted payment.
            $successStatuses = array_merge(PayzenApi::getSuccessStatuses(), PayzenApi::getPendingStatuses());

            $this->restHelper->checkResult($getPaymentDetails, $successStatuses);

            // Check operation type.
            $transType = $getPaymentDetails['answer']['operationType'];
            if ($transType !== 'DEBIT') {
                throw new \Exception("Unexpected transaction type returned ($transType).");
            }

            $this->dataHelper->log("Updating payment information for accepted order #{$order->getIncrementId()}.");

            // Payment is accepted by merchant.
            $payment->setIsFraudDetected(false);

            // Wrap payment result to use traditional order creation tunnel.
            $data = $this->restHelper->convertRestResult($getPaymentDetails['answer'], true);

            // Load API response.
            $response = $this->payzenResponseFactory->create(
                [
                    'params' => $data,
                    'ctx_mode' => null,
                    'key_test' => null,
                    'key_prod' => null,
                    'algo' => null
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
        } catch(\UnexpectedValueException $e) {
            $this->dataHelper->log(
                "getPaymentDetails error with code {$e->getCode()}: {$e->getMessage()}.",
                \Psr\Log\LogLevel::ERROR
            );

            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "Exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            if ($e->getCode() <= -1) { // Manage cUrl errors.
                $message = __("Please consult the PayZen logs for more details.");
            } else {
                $message = $e->getMessage();
            }

            $this->messageManager->addError($message);

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

        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        // Client has not configured private key in module backend, let Magento accept order offline.
        if (! $this->restHelper->getPrivateKey($storeId)) {
            $this->dataHelper->log("Cannot cancel payment online for order #{$order->getIncrementId()}: private key is not configured, let Magento cancel the payment.");

            $this->messageManager->addWarning(__('Payment is cancelled only in Magento. Please, consider cancelling the payment in PayZen Back Office.'));
            return true;
        }

        $this->dataHelper->log("Cancel payment online for order #{$order->getIncrementId()}.");

        try {
            // Retrieve transaction UUID.
            $uuid = $payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID);
            if (! $uuid) { // Retro compatibility.
                // Get UUID from Order.
                $uuidArray = $this->getPaymentDetails($order);
                $uuid = reset($uuidArray);
            }

            $requestData = [
                'uuid' => $uuid,
                'resolutionMode' => 'CANCELLATION_ONLY',
                'comment' => $this->getUserInfo()
            ];

            // Perform our request.
            $client = new PayzenRest(
                $this->dataHelper->getCommonConfigData('rest_url', $storeId),
                $this->dataHelper->getCommonConfigData('site_id', $storeId),
                $this->restHelper->getPrivateKey($storeId)
            );

            $cancelPaymentResponse = $client->post('V4/Transaction/CancelOrRefund', json_encode($requestData));
            $this->restHelper->checkResult($cancelPaymentResponse, ['CANCELLED']);

            $this->dataHelper->log("Payment cancelled successfully online for order #{$order->getIncrementId()}.");

            $transactionId = $payment->getCcTransId() . '-' . $cancelPaymentResponse['answer']['transactionDetails']['sequenceNumber'];
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
        } catch(\UnexpectedValueException $e) {
            $this->dataHelper->log(
                "cancelPayment error with code {$e->getCode()}: {$e->getMessage()}.",
                \Psr\Log\LogLevel::ERROR
            );

            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "Exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            if ($e->getCode() <= -1) { // Manage cUrl errors.
                $message = __("Please consult the PayZen logs for more details.");
            } else {
                $message = $e->getMessage();
            }

            $this->messageManager->addError($message);

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
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $uuidArray = [];
        $onlineValidatePayment = true;

        if (! $this->restHelper->getPrivateKey($storeId)) {
            // Client has not configured private key in module backend, let Magento validate payment offline.
            $this->dataHelper->log("Cannot validate online payment for order #{$order->getIncrementId()}: private key is not configured, let Magento validate payment.");
            $this->messageManager->addWarning(__('Payment is validated only in Magento. Please, consider validating the payment in PayZen Back Office.'));
            $onlineValidatePayment = false;
        } else {
            $this->dataHelper->log("Validate payment online for order #{$order->getIncrementId()}.");
        }

        try {
            // Get choosen payment option if any.
            $option = @unserialize($payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION));
            $multi = (stripos($payment->getMethod(), 'payzen_multi') === 0) && is_array($option) && ! empty($option);
            $count = $multi ? (int) $option['count'] : 1;

            if ($onlineValidatePayment) {
                // Retrieve saved transaction UUID.
                $savedUuid = $payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID);

                if (! $savedUuid || ($count > 1)) {
                    $uuidArray = $this->getPaymentDetails($order);
                } else {
                    $uuidArray[] = $savedUuid;
                }

                $first = true;
                foreach ($uuidArray as $uuid) {
                    $requestData = [
                        'uuid' => $uuid,
                        'comment' => $this->getUserInfo()
                    ];

                    // Perform our request.
                    $client = new PayzenRest(
                        $this->dataHelper->getCommonConfigData('rest_url', $storeId),
                        $this->dataHelper->getCommonConfigData('site_id', $storeId),
                        $this->restHelper->getPrivateKey($storeId)
                    );

                    $validatePaymentResponse = $client->post('V4/Transaction/Validate', json_encode($requestData));

                    $this->restHelper->checkResult($validatePaymentResponse, ['WAITING_AUTHORISATION', 'AUTHORISED']);

                    // Wrap payment result to use traditional order creation tunnel.
                    $data = $this->restHelper->convertRestResult($validatePaymentResponse['answer'], true);

                    // Load API response.
                    $response = $this->payzenResponseFactory->create(
                        [
                            'params' => $data,
                            'ctx_mode' => null,
                            'key_test' => null,
                            'key_prod' => null,
                            'algo' => null
                        ]
                    );

                    $transId = $order->getPayment()->getCcTransId() . '-' . $response->get('sequence_number');

                    if ($first) { // Single payment or first transaction for payment in installments.
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

                    $first = false;
                }
            } else {
                // Wrap payment result to use traditional order creation tunnel.
                $data = ['vads_trans_status' => 'AUTHORISED'];

                $txn = $this->transactionResource->loadObjectByTxnId(
                    $this->transaction,
                    $order->getId(),
                    $order->getPayment()->getId(),
                    $order->getPayment()->getLastTransId()
                );

                if ($txn && $txn->getId()) {
                    $txnData = $txn->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);
                    $data['vads_card_brand'] = $txnData['Means of payment'];
                }

                // Load API response.
                $response = $this->payzenResponseFactory->create(
                    [
                        'params' => $data,
                        'ctx_mode' => null,
                        'key_test' => null,
                        'key_prod' => null,
                        'algo' => null
                    ]
                );

                $stateObject = $this->paymentHelper->nextOrderState($order, $response, true);

                $this->dataHelper->log("Order #{$order->getIncrementId()}, new state : {$stateObject->getState()}, new status : {$stateObject->getStatus()}.");
                $order->setState($stateObject->getState())
                      ->setStatus($stateObject->getStatus());

                $order->addStatusHistoryComment(__('Order %1 has been validated.', $order->getIncrementId()));
            }

            $this->dataHelper->log("Updating payment information for validated order #{$order->getIncrementId()}.");

            // Try to create invoice.
            $this->paymentHelper->createInvoice($order);

            $order->save();
            $this->messageManager->addSuccess(__('Payment validated successfully.'));
        } catch(\UnexpectedValueException $e) {
            $this->dataHelper->log(
                "validatePayment error with code {$e->getCode()}: {$e->getMessage()}.",
                \Psr\Log\LogLevel::ERROR
            );
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "Exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            $message = __("We cannot update the payment right now.");
            if ($e->getCode() <= -1) { // Manage cUrl errors.
                $message .= ' ' . __("Please consult the PayZen logs for more details.");
            } else {
                $message .= ' ' . $e->getMessage();
            }

            $this->messageManager->addError($message);
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
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $this->dataHelper->log("Start refund of {$amount} {$order->getOrderCurrencyCode()} for order " .
             "#{$order->getIncrementId()} with {$this->_code} payment method.");

        try {
            // Get currency.
            $currency = PayzenApi::findCurrencyByAlphaCode($order->getOrderCurrencyCode());

            // Retrieve transaction UUID.
            $uuid = $payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID);
            if (! $uuid) { // Retro compatibility.
                // Get UUID from Order.
                $uuidArray = $this->getPaymentDetails($order);
                $uuid = reset($uuidArray);
            }

            $requestData = ['uuid' => $uuid];

            // Perform our request.
            $client = new PayzenRest(
                $this->dataHelper->getCommonConfigData('rest_url', $storeId),
                $this->dataHelper->getCommonConfigData('site_id', $storeId),
                $this->restHelper->getPrivateKey($storeId)
            );

            $getPaymentDetails = $client->post('V4/Transaction/Get', json_encode($requestData));

            $this->restHelper->checkResult($getPaymentDetails);

            $transStatus = $getPaymentDetails['answer']['detailedStatus'];
            $amountInCents = $currency->convertAmountToInteger($amount);

            $commentText = $this->getUserInfo();

            foreach ($payment->getCreditmemo()->getComments() as $comment) {
                $commentText .= '; ' . $comment->getComment();
            }

            if ($transStatus === 'CAPTURED') { // Transaction captured, we can do refund.
                $requestData = [
                    'uuid' => $uuid,
                    'amount' => $amountInCents,
                    'resolutionMode' => 'REFUND_ONLY',
                    'currency' => $currency->getAlpha3(),
                    'comment' => $commentText
                ];

                $refundPaymentResponse = $client->post('V4/Transaction/CancelOrRefund', json_encode($requestData));

                // Pending or accepted payment.
                $successStatuses = array_merge(PayzenApi::getSuccessStatuses(), PayzenApi::getPendingStatuses());

                $this->restHelper->checkResult($refundPaymentResponse, $successStatuses);

                // Check operation type.
                $transType = $refundPaymentResponse['answer']['operationType'];

                if ($transType !== 'CREDIT') {
                    throw new \Exception("Unexpected transaction type returned ($transType).");
                }

                // Create refund transaction in Magento.
                $this->createRefundTransaction($payment, $refundPaymentResponse['answer']);

                $this->dataHelper->log("Online money refund for order #{$order->getIncrementId()} is successful.");
            } else {
                $transAmount = $getPaymentDetails['answer']['amount'];

                if ($amountInCents >= $transAmount) { // Transaction cancel.
                    $requestData = [
                        'uuid' => $uuid,
                        'resolutionMode' => 'CANCELLATION_ONLY',
                        'comment' => $commentText
                    ];

                    $cancelPaymentResponse = $client->post('V4/Transaction/CancelOrRefund', json_encode($requestData));

                    $this->restHelper->checkResult($cancelPaymentResponse, ['CANCELLED']);

                    $order->cancel();
                    $this->dataHelper->log("Online payment cancel for order #{$order->getIncrementId()} is successful.");
                } else {
                    // Partial transaction cancel, call update WS.
                    $timestamp = time();

                    $captureDelay = $this->getConfigData('capture_delay', $storeId); // Get submodule specific param.
                    if (! is_numeric($captureDelay)) {
                        // Get general param.
                        $captureDelay = $this->dataHelper->getCommonConfigData('capture_delay', $storeId);
                    }

                    $expectedCaptureDate = (is_numeric($captureDelay)) ? new \DateTime('@' . strtotime("+$captureDelay days", $timestamp)) : null;

                    $validationMode = $this->getConfigData('validation_mode', $storeId); // Get submodule specific param.
                    if ($validationMode === '-1') {
                        // Get general param.
                        $validationMode = $this->dataHelper->getCommonConfigData('validation_mode', $storeId);
                    }

                    if ($validationMode !== '') {
                        $validationMode = ($validationMode == '1') ? 'YES' : 'NO';
                    }

                    $requestData = [
                        'uuid' => $uuid,
                        'cardUpdate' => [
                            'amount' => $transAmount - $amountInCents,
                            'currency' => $currency->getAlpha3(),
                            'expectedCaptureDate' => $expectedCaptureDate,
                            'manualValidation' => $validationMode
                        ],
                        'comment' => $commentText
                    ];

                    $updatePaymentResponse = $client->post('V4/Transaction/Update', json_encode($requestData));

                    $this->restHelper->checkResult($updatePaymentResponse,
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
        } catch(\UnexpectedValueException $e) {
            $this->dataHelper->log("refund error with code {$e->getCode()}: {$e->getMessage()}.", \Psr\Log\LogLevel::ERROR);
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "Exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            $message = __('Refund error') . ':';
            if ($e->getCode() <= -1) { // Manage cUrl errors.
                $message .= ' ' . __('Please consult the PayZen logs for more details.');
            } else {
                $message .= ' ' . $e->getMessage();
            }

            throw new \Exception($message);
        }

        $order->save();
        return $this;
    }

    private function createRefundTransaction($payment, $refundResponse)
    {
        $response = $this->restHelper->convertRestResult($refundResponse, true);

        // Save transaction details to sales_payment_transaction.
        $transactionId = $response['vads_trans_id']. '-' . $response['vads_sequence_number'];

        $expiry = '';
        if ($response['vads_expiry_month'] && $response['vads_expiry_year']) {
            $expiry = str_pad($response['vads_expiry_month'], 2, '0', STR_PAD_LEFT) . ' / ' .
                $response['vads_expiry_year'];
        }

        // Save paid amount.
        $currency = PayzenApi::findCurrencyByNumCode($response['vads_currency']);
        $amount = round($currency->convertAmountToFloat($response['vads_amount']), $currency->getDecimals());

        $amountDetail = $amount . ' ' . $currency->getAlpha3();

        if (isset($response['vads_effective_currency']) &&
            ($response['vads_currency'] !== $response['vads_effective_currency'])) {
                $effectiveCurrency = PayzenApi::findCurrencyByNumCode($response['vads_effective_currency']);

            $effectiveAmount = round(
                $effectiveCurrency->convertAmountToFloat($response['vads_effective_amount']),
                $effectiveCurrency->getDecimals()
            );

            $amountDetail = $effectiveAmount . ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
        }

        $additionalInfo = [
            'Transaction Type' => 'CREDIT',
            'Amount' => $amountDetail,
            'Transaction ID' => $transactionId,
            'Transaction UUID' => $response['vads_trans_uuid'],
            'Transaction Status' => $response['vads_trans_status'],
            'Means of payment' => $response['vads_card_brand'],
            'Card Number' => $response['vads_card_number'],
            'Expiration Date' => $expiry
        ];

        $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND;
        $this->paymentHelper->addTransaction($payment, $transactionType, $transactionId, $additionalInfo);
    }

    protected function getPaymentDetails($order, $uuidOnly = true)
    {
        $storeId = $order->getStore()->getId();

        // Get UUIDs from Order.
        $client = new PayzenRest(
            $this->dataHelper->getCommonConfigData('rest_url', $storeId),
            $this->dataHelper->getCommonConfigData('site_id', $storeId),
            $this->restHelper->getPrivateKey($storeId)
        );

        $requestData = [
            'orderId' => $order->getIncrementId(),
            'operationType' => 'DEBIT'
        ];

        $getOrderResponse = $client->post('V4/Order/Get', json_encode($requestData));
        $this->restHelper->checkResult($getOrderResponse);

        // Order transactions organized by sequence numbers.
        $transBySequence = [];
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

    protected function getUserInfo()
    {
        $commentText = 'Magento user: ' . $this->authSession->getUser()->getUsername();
        $commentText .= '; IP address: ' . $this->dataHelper->getIpAddress();

        return $commentText;
    }

    /**
     * Return logged in customer model data.
     *
     * @return int
     */
    public function getCurrentCustomer()
    {
        return $this->dataHelper->getCurrentCustomer($this->customerSession);
    }
}
