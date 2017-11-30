<?php
/**
 * PayZen V2-Payment Module version 2.1.3 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */
namespace Lyranetwork\Payzen\Model\Method;

use Lyranetwork\Payzen\Model\Api\PayzenApi;

abstract class Payzen extends \Magento\Payment\Model\Method\AbstractMethod
{

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

    protected $_canReviewPayment = false;

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
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Lyranetwork\Payzen\Model\Api\PayzenRequest $payzenRequest
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     * @param \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Module\Dir\Reader $dirReader
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
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
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Payment $paymentHelper,
        \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Module\Dir\Reader $dirReader,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        $this->localeResolver = $localeResolver;
        $this->payzenRequest = $payzenRequestFactory->create();
        $this->dataHelper = $dataHelper;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->productMetadata = $productMetadata;
        $this->messageManager = $messageManager;
        $this->dirReader = $dirReader;
        $this->dataObjectFactory = $dataObjectFactory;

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
        // set order_id
        $this->payzenRequest->set('order_id', $order->getIncrementId());

        // Amount in current order currency
        $amount = $order->getGrandTotal();

        // set currency
        $currency = PayzenApi::findCurrencyByAlphaCode($order->getOrderCurrencyCode());
        if ($currency == null) {
            // If currency is not supported, use base currency
            $currency = PayzenApi::findCurrencyByAlphaCode($order->getBaseCurrencyCode());

            // ... and order total in base currency
            $amount = $order->getBaseGrandTotal();
        }
        $this->payzenRequest->set('currency', $currency->getNum());

        // set the amount to pay
        $this->payzenRequest->set('amount', $currency->convertAmountToInteger($amount));

        // contrib info
        $version = $this->productMetadata->getVersion(); // will return the magento version
        $this->payzenRequest->set('contrib', 'Magento2.x_2.1.3/' . $version . '/' . PHP_VERSION);

        // set config parameters
        $configFields = [
            'site_id',
            'key_test',
            'key_prod',
            'ctx_mode',
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

        // check if capture_delay and validation_mode are overriden in sub-modules
        if (is_numeric($this->getConfigData('capture_delay'))) {
            $this->payzenRequest->set('capture_delay', $this->getConfigData('capture_delay'));
        }
        if ($this->getConfigData('validation_mode') !== '-1') {
            $this->payzenRequest->set('validation_mode', $this->getConfigData('validation_mode'));
        }

        // set return url (build it and add store_id)
        $storeId = $this->dataHelper->isBackend() ? null : $order->getStore()->getId();
        $returnUrl = $this->dataHelper->getReturnUrl($storeId);

        $this->dataHelper->log('The complete return URL is ' . $returnUrl);
        $this->payzenRequest->set('url_return', $returnUrl);

        // set the language code
        $lang = strtolower(substr($this->localeResolver->getLocale(), 0, 2));
        if (! PayzenApi::isSupportedLanguage($lang)) {
            $lang = $this->dataHelper->getCommonConfigData('language');
        }
        $this->payzenRequest->set('language', $lang);

        // available_languages is given as csv by magento
        $availableLanguages = explode(',', $this->dataHelper->getCommonConfigData('available_languages'));
        $availableLanguages = in_array('', $availableLanguages) ? '' : implode(';', $availableLanguages);
        $this->payzenRequest->set('available_languages', $availableLanguages);

        // activate 3ds ?
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

        $address = $order->getShippingAddress();
        if (is_object($address)) { // shipping is supported
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

        // set method-specific parameters
        $this->setExtraFields($order);

        // add cart data
        $this->checkoutHelper->setCartData($order, $this->payzenRequest);

        if ($this->sendOneyFields()) {
            // set other data specific to FacilyPay Oney payment
            $this->checkoutHelper->setOneyData($order, $this->payzenRequest);
        }

        $paramsToLog = $this->payzenRequest->getRequestFieldsArray(true);
        $this->dataHelper->log('Payment parameters : ' . json_encode($paramsToLog), \Psr\Log\LogLevel::DEBUG);

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
     * A flag to set that there will be redirect to third party after confirmation.
     *
     * @return bool
     */
    public function getOrderPlaceRedirectUrl()
    {
        return true;
    }

    /**
     * Return the payment platform URL.
     *
     * @return string
     */
    public function getPlatformUrl()
    {
        return $this->dataHelper->getCommonConfigData('platform_url');
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
            \Lyranetwork\Payzen\Helper\Payment::ONEY_OPTION,
            \Lyranetwork\Payzen\Helper\Payment::CC_REGISTER,
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
     * Return an array of PayZen payment specific data.
     *
     * @param \Magento\Framework\DataObject $data
     * @return array[string][string]
     */
    public function extractPayzenData(\Magento\Framework\DataObject $data)
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

        // avoid sending order by e-mail before redirection
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
        // check selected currency support
        $currencyCode = '';
        $quote = $this->dataHelper->getCheckoutQuote();
        if ($quote && $quote->getId()) {
            $currencyCode = $quote->getQuoteCurrencyCode();
            $currency = PayzenApi::findCurrencyByAlphaCode($currencyCode);
            if ($currency) {
                return true;
            }
        }

        // check base currency support
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

        $configOptions = $this->dataHelper->unserialize($this->getConfigData('custgroup_amount_restrictions'));
        if (! is_array($configOptions) || empty($configOptions)) {
            return true;
        }

        $group = $quote && $quote->getCustomer() ? $quote->getCustomer()->getGroupId() : null;

        $allMinAmount = null;
        $allMaxAmount = null;
        $minAmount = null;
        $maxAmount = null;
        foreach ($configOptions as $key => $value) {
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
            // module will not be available
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
                 "#{$order->getId()} with {$this->_code} payment method.");

        try {
            $this->dataHelper->checkWsRequirements();

            // get currency
            $currency = PayzenApi::findCurrencyByAlphaCode($order->getOrderCurrencyCode());

            // headers generation
            $shopId = $this->dataHelper->getCommonConfigData('site_id', $storeId);
            $mode = $this->dataHelper->getCommonConfigData('ctx_mode', $storeId);
            $keyTest = $this->dataHelper->getCommonConfigData('key_test', $storeId);
            $keyProd = $this->dataHelper->getCommonConfigData('key_prod', $storeId);

            // load specific configuration file for WS
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

            $wsApi = new \Lyranetwork\Payzen\Model\Api\Ws\WsApi($options);
            $wsApi->init($shopId, $mode, $keyTest, $keyProd);

            $sid = false;

            // retrieve transaction UUID
            $uuid = $payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID);
            if (! $uuid) { // retro compatibility
                $legacyTransactionKeyRequest = new \Lyranetwork\Payzen\Model\Api\Ws\LegacyTransactionKeyRequest();
                $legacyTransactionKeyRequest->setTransactionId($payment->getCcTransId());
                $legacyTransactionKeyRequest->setSequenceNumber('1'); // only single payments can be refund
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

                // retrieve JSESSIONID created for getPaymentUuid call
                $sid = $wsApi->getJsessionId();
            }

            // common $queryRequest object to use in all operations
            $queryRequest = new \Lyranetwork\Payzen\Model\Api\Ws\QueryRequest();
            $queryRequest->setUuid($uuid);

            $getPaymentDetails = new \Lyranetwork\Payzen\Model\Api\Ws\GetPaymentDetails();
            $getPaymentDetails->setQueryRequest($queryRequest);

            $requestId = $wsApi->setHeaders();
            if ($sid) { // set JSESSIONID if ws getPaymentUuid is called
                $wsApi->setJsessionId($sid);
            }
            $getPaymentDetailsResponse = $wsApi->getPaymentDetails($getPaymentDetails);

            $wsApi->checkAuthenticity();
            $wsApi->checkResult($getPaymentDetailsResponse->getGetPaymentDetailsResult()->getCommonResponse());

            // retrieve JSESSIONID created for getPaymentDetails call
            if (! $sid) {
                $sid = $wsApi->getJsessionId();
            }

            $transStatus = $getPaymentDetailsResponse->getGetPaymentDetailsResult()
                ->getCommonResponse()
                ->getTransactionStatusLabel();
            $amountInCents = $currency->convertAmountToInteger($amount);

            // common request generation
            $commonRequest = new \Lyranetwork\Payzen\Model\Api\Ws\CommonRequest();
            $comment = '';
            foreach ($payment->getCreditmemo()->getCommentsCollection() as $comment) {
                $comment .= $comment->getComment() . ' ';
            }
            $commonRequest->setComment($comment);

            $requestId = $wsApi->setHeaders();
            $wsApi->setJsessionId($sid); // set JSESSIONID for the last ws call

            if ($transStatus === 'CAPTURED') { // transaction captured, we can do refund
                $timestamp = time();

                $paymentRequest = new \Lyranetwork\Payzen\Model\Api\Ws\PaymentRequest();
                $paymentRequest->setTransactionId(PayzenApi::generateTransId($timestamp));
                $paymentRequest->setAmount($amountInCents);
                $paymentRequest->setCurrency($currency->getNum());

                $captureDelay = $this->getConfigData('capture_delay', $storeId); // get sub-module specific param
                if (! is_numeric($captureDelay)) {
                    // get general param
                    $captureDelay = $this->dataHelper->getCommonConfigData('capture_delay', $storeId);
                }

                if (is_numeric($captureDelay)) {
                    $paymentRequest->setExpectedCaptureDate(new \DateTime('@' . strtotime("+$captureDelay days", $timestamp)));
                }

                $validationMode = $this->getConfigData('validation_mode', $storeId); // get sub-module specific param
                if ($validationMode === '-1') {
                    // get general param
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
                    $refurndPaymentResponse->getRefundPaymentResult()
                    ->getCommonResponse(),
                    [
                        'INITIAL',
                        'AUTHORISED',
                        'AUTHORISED_TO_VALIDATE',
                        'WAITING_AUTHORISATION',
                        'WAITING_AUTHORISATION_TO_VALIDATE'
                    ]
                );

                // check operation type (0: debit, 1 refund)
                $transType = $refurndPaymentResponse->getRefundPaymentResult()
                    ->getPaymentResponse()
                    ->getOperationType();
                if ($transType != 1) {
                    throw new \Exception("Unexpected transaction type returned ($transType).");
                }

                // create refund transaction in Magento
                $this->createRefundTransaction(
                    $payment,
                    $refurndPaymentResponse->getRefundPaymentResult()
                        ->getCommonResponse(),
                    $refurndPaymentResponse->getRefundPaymentResult()
                        ->getPaymentResponse(),
                    $refurndPaymentResponse->getRefundPaymentResult()
                    ->getCardResponse()
                );

                $this->dataHelper->log("Online money refund for order #{$order->getId()} is successful.");
            } else {
                $transAmount = $getPaymentDetailsResponse->getGetPaymentDetailsResult()
                    ->getPaymentResponse()
                    ->getAmount();
                if ($amountInCents >= $transAmount) { // transaction cancel
                    $cancelPayment = new \Lyranetwork\Payzen\Model\Api\Ws\CancelPayment();
                    $cancelPayment->setCommonRequest($commonRequest);
                    $cancelPayment->setQueryRequest($queryRequest);

                    $cancelPaymentResponse = $wsApi->cancelPayment($cancelPayment);

                    $wsApi->checkAuthenticity();
                    $wsApi->checkResult($cancelPaymentResponse->getCancelPaymentResult()
                        ->getCommonResponse(), [
                        'CANCELLED'
                        ]);

                    $order->cancel();
                    $this->dataHelper->log("Online payment cancel for order #{$order->getId()} is successful.");
                } else { // partial transaction cancel, call updatePayment WS
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
                        $updatePaymentResponse->getUpdatePaymentResult()
                        ->getCommonResponse(),
                        [
                            'AUTHORISED',
                            'AUTHORISED_TO_VALIDATE',
                            'WAITING_AUTHORISATION',
                            'WAITING_AUTHORISATION_TO_VALIDATE'
                        ]
                    );
                    $this->dataHelper->log("Online payment update for order #{$order->getId()} is successful.");
                }
            }
        } catch (\Lyranetwork\Payzen\Model\WsException $e) {
            $this->dataHelper->log("[$requestId] {$e->getMessage()}", \Psr\Log\LogLevel::WARNING);

            $this->messageManager->addError($e->getMessage());
            $this->messageManager->addWarning('Please correct error to refund payments through PayZen. If you want to refund order in Magento, use the &laquo;Refund Offline&raquo; button.');
            throw new \Exception($e->getMessage());
        } catch (\SoapFault $f) {
            $this->dataHelper->log(
                "[$requestId] SoapFault with code {$f->faultcode}: {$f->faultstring}.",
                \Psr\Log\LogLevel::WARNING
            );

            $this->messageManager->addWarning('Please correct error to refund payments through PayZen. If you want to refund order in Magento, use the &laquo;Refund Offline&raquo; button.');
            $this->messageManager->addError($f->faultstring);
            throw new \Exception($message);
        } catch (\Lyranetwork\Payzen\Model\Api\Ws\SecurityException $e) {
            $this->dataHelper->log("[$requestId] " . $e->getMessage(), \Psr\Log\LogLevel::ERROR);

            $this->messageManager->addError('Authentication error !');
            throw new \Exception($e->getMessage());
        } catch (\Lyranetwork\Payzen\Model\Api\Ws\ResultException $e) {
            $this->dataHelper->log(
                "[$requestId] Refund error with code {$e->getRealCode()}: {$e->getMessage()}.",
                \Psr\Log\LogLevel::WARNING
            );

            if ($e->getCode() == 1) { // merchant does not subscribe to WS option
                $this->messageManager->addWarning('You are not authorized to do this action online. Please, do not forget to update payment in PayZen Back Office.');
                // magento will do an offline refund
            } else {
                $message = __('Refund error')->render() . ': ' . $e->getMessage();
                $this->messageManager->addError($message);
                throw new \Exception($e->getMessage());
            }
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "[$requestId] Exception with code {$e->getCode()}: {$e->getMessage()}.",
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

        // save transaction details to sales_payment_transaction
        $transactionId = $paymentResponse->getTransactionId() . '-' . $paymentResponse->getSequenceNumber();

        $expiry = '';
        if ($cardResponse->getExpiryMonth() && $cardResponse->getExpiryYear()) {
            $expiry = str_pad($cardResponse->getExpiryMonth(), 2, '0', STR_PAD_LEFT) . ' / ' .
                 $cardResponse->getExpiryYear();
        }

        // save paid amount
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
            'Transaction Status' => $commonResponse->getTransactionStatusLabel(),
            'Payment Mean' => $cardResponse->getBrand(),
            'Credit Card Number' => $cardResponse->getNumber(),
            'Expiration Date' => $expiry,
            '3-DS Certificate' => ''
        ];

        $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND;
        $this->paymentHelper->addTransaction($payment, $transactionType, $transactionId, $additionalInfo);
    }
}
