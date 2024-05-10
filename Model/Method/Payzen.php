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

use Lyranetwork\Payzen\Model\Api\Form\Api as PayzenApi;
use Lyranetwork\Payzen\Model\Api\Rest\Api as PayzenRest;
use Lyranetwork\Payzen\Model\Api\Refund\Api as PayzenRefund;
use Lyranetwork\Payzen\Model\Api\Refund\OrderInfo as PayzenOrderInfo;

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
    protected $needsShippingMethodData = false;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Lyranetwork\Payzen\Model\Api\Form\Request
     */
    protected $payzenRequest;

    /**
     * @var \Lyranetwork\Payzen\Model\Api\Form\Response
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
     * @var \Magento\Framework\App\Response\Http
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
     * @var \Lyranetwork\Payzen\Helper\Refund
     */
    protected $refundHelper;

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
     * @param \Lyranetwork\Payzen\Model\Api\Form\Request $payzenRequest
     * @param \Lyranetwork\Payzen\Model\Api\Form\ResponseFactory $payzenResponseFactory
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction $transactionResource
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\Response\Http $redirect
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     * @param \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper
     * @param \Lyranetwork\Payzen\Helper\Rest $restHelper
     * @param \Lyranetwork\Payzen\Helper\Refund $refundHelper
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
        \Lyranetwork\Payzen\Model\Api\Form\RequestFactory $payzenRequestFactory,
        \Lyranetwork\Payzen\Model\Api\Form\ResponseFactory $payzenResponseFactory,
        \Magento\Sales\Model\Order\Payment\Transaction $transaction,
        \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction $transactionResource,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Response\Http $redirect,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Payment $paymentHelper,
        \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper,
        \Lyranetwork\Payzen\Helper\Rest $restHelper,
        \Lyranetwork\Payzen\Helper\Refund $refundHelper,
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
        $this->refundHelper = $refundHelper;
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
        if (! $currency) {
            // If currency is not supported, use base currency.
            $currency = PayzenApi::findCurrencyByAlphaCode($order->getBaseCurrencyCode());

            // ... and order total in base currency
            $amount = $order->getBaseGrandTotal();
        }

        $this->payzenRequest->set('currency', $currency->getNum());

        // Set the amount to pay.
        $this->payzenRequest->set('amount', $currency->convertAmountToInteger($amount));

        // Contrib info.
        $this->payzenRequest->set('contrib',  $this->dataHelper->getContribParam());

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
        $availableLanguages = $this->dataHelper->explode(',', $this->dataHelper->getCommonConfigData('available_languages'));
        $availableLanguages = in_array('', $availableLanguages) ? '' : implode(';', $availableLanguages);
        $this->payzenRequest->set('available_languages', $availableLanguages);

        // Activate 3ds?
        $threedsMpi = null;
        $threedsMinAmount = $this->dataHelper->getCommonConfigData('threeds_min_amount');
        if (! empty($threedsMinAmount) && ($order->getTotalDue() < $threedsMinAmount)) {
            $threedsMpi = '2';
        }

        // Sanitize phone number before sending it to the gateway.
        $telephone = str_replace([' ', '.', '-'], '', $order->getBillingAddress()->getTelephone() ? $order->getBillingAddress()->getTelephone() : '');

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
        $this->payzenRequest->set('cust_phone', $telephone);
        $this->payzenRequest->set('cust_cell_phone', $telephone);

        $address = $order->getShippingAddress();
        if (is_object($address)) { // Shipping is supported.
            $this->payzenRequest->set('ship_to_first_name', $address->getFirstname());
            $this->payzenRequest->set('ship_to_last_name', $address->getLastname());
            $this->payzenRequest->set('ship_to_city', $address->getCity());
            $this->payzenRequest->set('ship_to_street', $address->getStreetLine(1));
            $this->payzenRequest->set('ship_to_street2', $address->getStreetLine(2));
            $this->payzenRequest->set('ship_to_state', $address->getRegionCode());
            $this->payzenRequest->set('ship_to_country', $address->getCountryId());
            $this->payzenRequest->set('ship_to_phone_num', str_replace([' ', '.', '-'], '', $address->getTelephone() ? $address->getTelephone() : ''));
            $this->payzenRequest->set('ship_to_zip', $address->getPostcode());
        }

        $features = \Lyranetwork\Payzen\Helper\Data::$pluginFeatures;
        if ($features['brazil']) {
            $this->payzenRequest->set('cust_national_id', $this->getCustomerData($order, 'cpf'));
            $this->payzenRequest->set('cust_address_number', $this->getCustomerData($order, 'street'));
            $this->payzenRequest->set('cust_district', $this->getCustomerData($order, 'district'));

            if (is_object($address)) {
                $this->payzenRequest->set('ship_to_user_info', $this->getCustomerData($order, 'cpf'), false);
                $this->payzenRequest->set('ship_to_address_number', $this->getCustomerData($order, 'street'), false);
                $this->payzenRequest->set('ship_to_district', $this->getCustomerData($order, 'district'), false);
            }
        }

        // Set method-specific parameters.
        $this->setExtraFields($order);

        $sendCartDetails = $this->dataHelper->getCommonConfigData('send_cart_detail') &&
            ($order->getTotalItemCount() <= self::CART_MAX_NB_PRODUCTS);

        // Add cart data.
        if ($sendCartDetails || $this->needsCartData /* Cart data are mandatory for the payment method. */) {
            $this->checkoutHelper->setCartData($order, $this->payzenRequest);
        }

        // Add information about delivery mode.
        if ($this->needsShippingMethodData /* Shipping method data are mandatory for the payment method. */) {
            $this->checkoutHelper->setAdditionalShippingData($order, $this->payzenRequest);
        }

        $paramsToLog = $this->payzenRequest->getRequestFieldsArray(true);
        $this->dataHelper->log('Payment parameters: ' . json_encode($paramsToLog));

        return $this->payzenRequest->getRequestFieldsArray(false, false);
    }

    abstract protected function setExtraFields($order);

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
        }

        return $data;
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
            $this->dataHelper->log("Cannot get online payment information for order #{$order->getIncrementId()}: private key is not configured, let Magento accept the payment.");

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
                throw new \UnexpectedValueException("Unexpected transaction type returned ($transType).");
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
                    'key_test' => '',
                    'key_prod' => '',
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

            $this->dataHelper->log("Saving accepted order #{$order->getIncrementId()}.");
            $order->save();
            $this->dataHelper->log("Accepted order #{$order->getIncrementId()} has been saved.");

            $this->messageManager->addSuccessMessage(__('The payment has been accepted.'));

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
                "Get payment details error: {$e->getMessage()}.",
                \Psr\Log\LogLevel::ERROR
            );

            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "Get payment details exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            if ($e->getCode() === 'PSP_100') {
                // Merchant does not subscribe to REST WS option, accept payment offline.
                $this->dataHelper->log("Cannot get online payment information for order #{$order->getIncrementId()}: REST API not available for merchant, let Magento accept the payment.");

                return true;
            } else {
                $message = __('Payment review error') . ': ';

                if ($e->getCode() <= -1) {
                    // Manage cUrl errors.
                    $message .= __('Please consult the PayZen logs for more details.');
                } else {
                    $message .= $e->getMessage();
                }

                $this->messageManager->addErrorMessage($message);
                throw $e;
            }
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

        // Client has not configured private key in module backend, let Magento cancel order offline.
        if (! $this->restHelper->getPrivateKey($storeId)) {
            $this->dataHelper->log("Cannot cancel payment online for order #{$order->getIncrementId()}: private key is not configured, let Magento cancel the payment.");

            $this->messageManager->addWarningMessage(__('Payment is cancelled only in Magento. Please, consider cancelling the payment in PayZen Back Office.'));
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
                "Cancel payment error: {$e->getMessage()}.",
                \Psr\Log\LogLevel::ERROR
            );

            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "Cancel payment exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            if ($e->getCode() === 'PSP_100') {
                // Merchant does not subscribe to REST WS option, deny payment offline.
                $this->dataHelper->log("Cannot cancel payment online for order #{$order->getIncrementId()}: REST API not available for merchant, let Magento cancel the payment.");

                $this->messageManager->addWarningMessage(__('Payment is cancelled only in Magento. Please, consider cancelling the payment in PayZen Back Office.'));
                return true;
            } else {
                $message = __('Cancellation error') . ': ';

                if ($e->getCode() <= -1) {
                    // Manage cUrl errors.
                    $message .= __('Please consult the PayZen logs for more details.');
                } else {
                    $message .= $e->getMessage();
                }

                $this->messageManager->addErrorMessage($message);
                throw $e;
            }
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
        return $this->payzenValidatePayment($payment);
    }

    protected function payzenValidatePayment($payment, $createInvoice = true)
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $order = $payment->getOrder();
        $storeId = $order->getStore()->getId();

        $uuidArray = [];

        if (! $this->restHelper->getPrivateKey($storeId)) {
            // Client has not configured private key in module backend, let's update order offline.
            $this->dataHelper->log("Cannot validate online payment for order #{$order->getIncrementId()}: private key is not configured, let's validate order offline.");
            $this->validatePaymentOffline($order);

            return;
        }

        $this->dataHelper->log("Validate payment online for order #{$order->getIncrementId()}.");

        try {
            // Get choosen payment option if any.
            $option = @unserialize($payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION));
            $multi = (stripos($payment->getMethod(), 'payzen_multi') === 0) && is_array($option) && ! empty($option);
            $count = $multi ? (int) $option['count'] : 1;

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
                        'key_test' => '',
                        'key_prod' => '',
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

            // Try to create invoice.
            if ($createInvoice) {
                $this->paymentHelper->createInvoice($order);
            }

            $this->dataHelper->log("Saving validated order #{$order->getIncrementId()}.");
            $order->save();
            $this->dataHelper->log("Validated order #{$order->getIncrementId()} has been saved.");

            $this->dataHelper->log("Payment information updated for validated order #{$order->getIncrementId()}.");
            $this->messageManager->addSuccessMessage(__('Payment validated successfully.'));
        } catch(\UnexpectedValueException $e) {
            $this->dataHelper->log(
                "Validate payment error: {$e->getMessage()}.",
                \Psr\Log\LogLevel::ERROR
            );

            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            $this->dataHelper->log(
                "Validate payment exception with code {$e->getCode()}: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            if ($e->getCode() === 'PSP_100') {
                // Merchant does not subscribe to REST WS option, validate payment offline.
                $this->dataHelper->log("Cannot validate online payment for order #{$order->getIncrementId()}: REST API not available for merchant, let's validate order offline.");
                $this->validatePaymentOffline($order, true);

                return;
            } else {
                $message = __('Validation error') . ': ';

                if ($e->getCode() <= -1) {
                    // Manage cUrl errors.
                    $message .= __('Please consult the PayZen logs for more details.');
                } else {
                    $message .= $e->getMessage();
                }

                $this->messageManager->addErrorMessage($message);
            }
        }
    }

    protected function validatePaymentOffline($order)
    {
        $this->messageManager->addWarningMessage(__('Payment is validated only in Magento. Please, consider validating the payment in PayZen Back Office.'));

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
                'key_test' => '',
                'key_prod' => '',
                'algo' => null
            ]
        );

        $stateObject = $this->paymentHelper->nextOrderState($order, $response, true);

        $this->dataHelper->log("Order #{$order->getIncrementId()}, new state : {$stateObject->getState()}, new status : {$stateObject->getStatus()}.");
        $order->setState($stateObject->getState())
              ->setStatus($stateObject->getStatus());

        $order->addStatusHistoryComment(__('Order %1 has been validated.', $order->getIncrementId()));

        // Try to create invoice.
        $this->paymentHelper->createInvoice($order);

        $this->dataHelper->log("Saving validated order #{$order->getIncrementId()}.");
        $order->save();
        $this->dataHelper->log("Validated order #{$order->getIncrementId()} has been saved.");;

        $this->dataHelper->log("Payment information updated for validated order #{$order->getIncrementId()}.");
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

        $this->dataHelper->log("Order #{$order->getIncrementId()} has been placed.");
        return $this;
    }

    /**
     * To check billing country is allowed for the payment method
     *
     * @param string $country
     * @return bool
     */
    public function canUseForCountry($country)
    {
        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = $this->dataHelper->explode(',', $this->getConfigData('specificcountry'));
            if (! in_array($country, $availableCountries)) {
                return false;
            }
        }

        return true;
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

        try {
            $commentText = $this->getUserInfo();
            foreach ($payment->getCreditmemo()->getComments() as $comment) {
                $commentText .= '; ' . $comment->getComment();
            }

            $payzenOrderInfo = new PayzenOrderInfo();
            $payzenOrderInfo->setOrderRemoteId($order->getIncrementId());
            $payzenOrderInfo->setOrderId($order->getIncrementId());
            $payzenOrderInfo->setOrderReference($order->getIncrementId());
            $payzenOrderInfo->setOrderCurrencyIsoCode($order->getBaseCurrencyCode());
            $payzenOrderInfo->setOrderCurrencySign($order->getBaseCurrencyCode());
            $payzenOrderInfo->setOrderUserInfo($commentText);

            $refundApi = new PayzenRefund(
                $this->refundHelper->setPayment($payment),
                $this->restHelper->getPrivateKey($storeId),
                $this->dataHelper->getCommonConfigData('rest_url', $storeId),
                $this->dataHelper->getCommonConfigData('site_id', $storeId),
                'Magento'
            );

            // Do online refund.
            $order->setPayment($payment);
            $refundApi->refund($payzenOrderInfo, $amount);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $order->save();
        $this->dataHelper->log("Refunded order #{$order->getIncrementId()} has been saved.");

        return $this;
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

    /**
     * Check capture availability.
     *
     * @return bool
     */
    public function canCapture()
    {
        if (! parent::canCapture()) {
            return false;
        }

        $payment = $this->getInfoInstance();

        $authTrans = $this->paymentHelper->getTransaction(
            \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH,
            $payment->getId(),
            $payment->getOrder()->getId()
        );

        if (! $authTrans || $authTrans->getIsClosed()) {
            return false;
        }

        return true;
    }

    /**
     * Capture payment.
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // Check if order capture on invoice is enabled.
        if (! $this->dataHelper->getCommonConfigData('invoice_capture')) {
            return;
        }

        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for capture.'));
        }

        $order = $payment->getOrder();
        $this->dataHelper->log("Capture payment called for #{$order->getIncrementId()}.");

        return $this->payzenValidatePayment($payment, false);
    }

    public function getCustomerData($order, $field, $billing = true)
    {
        $customerData = $this->dataHelper->unserialize($this->dataHelper->getCommonConfigData('customer_data'));
        if (is_array($customerData) && ! empty($customerData)) {
            switch ($field) {
                case "cpf":
                    $cpf = (key_exists('cpf', $customerData)) ? $customerData['cpf']['field'] : '';
                    if (! empty($cpf) && str_starts_with($cpf, 'customer_')) {
                        return (! empty($order->getData($cpf))) ? $this->dataHelper->formatCpfCpnj($order->getData($cpf)) : '';
                    } elseif (! empty($cpf) && str_starts_with($cpf, 'address_')) {
                        return $this->getOrderAddressAttribute($order, $cpf, true, $billing);
                    }

                    return '';
                case "district":
                case "street":
                    $district = (key_exists($field, $customerData)) ? $customerData[$field]['field'] : '';
                    if (! empty($district) && str_starts_with($district, 'customer_')) {
                        return (! empty($order->getData($district))) ? $order->getData($district) : '';
                    } elseif (! empty($district) && str_starts_with($district, 'address_')) {
                        return $this->getOrderAddressAttribute($order, $district, false, $billing);
                    }

                    return ($field == 'district') ? '-' : '0';
                default:
                    return '';
            }
        }

        return '';
    }

    public function getOrderAddressAttribute($order, $field, $cpf = false, $billing = true)
    {
        $fieldCode = substr_replace($field, "", strpos($field, 'address_'), strlen('address_'));
        if ($billing === true) {
            if ($order->getBillingAddress()->getData($fieldCode)) {
                return ($cpf === true) ? $this->dataHelper->formatCpfCpnj($order->getBillingAddress()->getData($fieldCode)) : $order->getBillingAddress()->getData($fieldCode);
            }
        } else {
            if ($order->getShippingAddress()->getData($fieldCode)) {
                return ($cpf === true) ? $this->dataHelper->formatCpfCpnj($order->getShippingAddress()->getData($fieldCode)) : $order->getShippingAddress()->getData($fieldCode);
            }
        }

        return '';
    }
}
