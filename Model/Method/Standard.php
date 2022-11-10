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

use Lyranetwork\Payzen\Helper\Data;

class Standard extends Payzen
{
    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_STANDARD;
    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Standard::class;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

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
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
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
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $localeResolver,
            $payzenRequestFactory,
            $payzenResponseFactory,
            $transaction,
            $transactionResource,
            $urlBuilder,
            $redirect,
            $dataHelper,
            $paymentHelper,
            $checkoutHelper,
            $restHelper,
            $refundHelper,
            $messageManager,
            $dirReader,
            $dataObjectFactory,
            $authSession,
            $resource,
            $resourceCollection,
            $data
        );
    }

    protected function setExtraFields($order)
    {
        $info = $this->getInfoInstance();

        if ($this->isLocalCcType()) {
            // Set payment_cards.
            $this->payzenRequest->set('payment_cards', $info->getCcType());
        } else {
            // Payment_cards is given as csv by magento.
            $paymentCards = $this->dataHelper->explode(',', $this->getConfigData('payment_cards'));
            $paymentCards = in_array('', $paymentCards) ? '' : implode(';', $paymentCards);

            $this->payzenRequest->set('payment_cards', $paymentCards);
        }

        // Set payment_src to MOTO for backend payments.
        if ($this->dataHelper->isBackend()) {
            $this->payzenRequest->set('payment_src', 'MOTO');
            $this->payzenRequest->set('return_mode', 'GET'); // Temporary workaround, TODO
            return;
        }

        if ($this->isIframeMode()) {
            // Iframe enabled.
            $this->payzenRequest->set('action_mode', 'IFRAME');

            // Hide logos below payment fields.
            $this->payzenRequest->set('theme_config', $this->payzenRequest->get('theme_config') . '3DS_LOGOS=false;');

            // Enable automatic redirection.
            $this->payzenRequest->set('redirect_enabled', '1');
            $this->payzenRequest->set('redirect_success_timeout', '0');
            $this->payzenRequest->set('redirect_error_timeout', '0');

            $returnUrl = $this->payzenRequest->get('url_return');
            $this->payzenRequest->set('url_return', $returnUrl . '?iframe=true');
        }

        if ($this->isOneClickActive() && $order->getCustomerId()) {
            // 1-Click enabled and customer logged-in.
            $customer = $this->customerRepository->getById($order->getCustomerId());

            if ($customer->getCustomAttribute('payzen_identifier') && $this->customerSession->getValidAlias()) {
                // Customer has an identifier.
                $this->payzenRequest->set('identifier', $customer->getCustomAttribute('payzen_identifier')->getValue());

                if (! $info->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::IDENTIFIER)) {
                    // Customer choose to not use alias.
                    $this->payzenRequest->set('page_action', 'REGISTER_UPDATE_PAY');
                }
            } else {
                // Bank data acquisition on payment page, let's ask customer for data registration.
                $this->dataHelper->log('Customer ' . $customer->getEmail() .
                     " will be asked for card data registration on payment page for order #{$order->getIncrementId()}.");
                $this->payzenRequest->set('page_action', 'ASK_REGISTER_PAY');
            }
        }

        $this->customerSession->unsetValidAlias();
    }

    /**
     * Return available card types.
     *
     * @return array[string][array]
     */
    public function getAvailableCcTypes()
    {
        if (! $this->isLocalCcType()) {
            return null;
        }

        // All cards.
        $allCards = \Lyranetwork\Payzen\Model\Api\Form\Api::getSupportedCardTypes();

        // Selected cards from module configuration.
        $cards = $this->getConfigData('payment_cards');

        if (! empty($cards)) {
            $cards = explode(',', $cards);
        } else {
            $cards = array_keys($allCards);
        }

        // Remove Oney card from payment means list.
        $cards = array_diff($cards, ['ONEY_3X_4X']);

        $availCards = [];
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

        if ($this->dataHelper->isBackend()) {
            return false;
        }

        if (! $this->isOneClickActive()) {
            return false;
        }

        // Customer has not gateway identifier.
        $customer = $this->getCurrentCustomer();
        if (! $customer || ! ($identifier = $customer->getCustomAttribute('payzen_identifier'))) {
            return false;
        }

        try {
            $aliasEnabled = $this->restHelper->checkIdentifier($identifier->getValue(), $customer->getEmail());
        }  catch (\Exception $e) {
            $this->dataHelper->log(
                "Saved identifier for customer {$customer->getEmail()} couldn't be verified on gateway. Error occurred: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            // Unable to validate alias online, we cannot disable feature.
            $aliasEnabled = true;
        }

        $this->customerSession->setValidAlias($aliasEnabled);
        return $aliasEnabled;
    }

    /**
     * Assign data to info model instance.
     *
     * @param array|\Magento\Framework\DataObject $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $info = $this->getInfoInstance();

        $payzenData = $this->extractPaymentData($data);

        $info->setCcType($payzenData->getData('payzen_standard_cc_type'));

        // Whether to do a payment by identifier.
        $info->setAdditionalInformation(
            \Lyranetwork\Payzen\Helper\Payment::IDENTIFIER,
            $payzenData->getData('payzen_standard_use_identifier')
        );

        return $this;
    }

    /**
     * Return true if iframe mode is enabled.
     *
     * @return bool
     */
    public function isIframeMode()
    {
        if ($this->dataHelper->isBackend()) {
            return false;
        }

        return $this->getEntryMode() == Data::MODE_IFRAME;
    }

    /**
     * Check if the local card type selection option is choosen.
     *
     * @return bool
     */
    public function isLocalCcType()
    {
        if ($this->dataHelper->isBackend()) {
            return false;
        }

        return $this->getEntryMode() == Data::MODE_LOCAL_TYPE;
    }

    /**
     * Check if embedded or popin mode is choosen.
     *
     * @return bool
     */
    public function isRestMode()
    {
        if ($this->dataHelper->isBackend()) {
            return false;
        }

        $restModes = [Data::MODE_EMBEDDED, Data::MODE_POPIN];
        return in_array($this->getEntryMode(), $restModes);
    }

    /**
     * Return card selection mode.
     *
     * @return int
     */
    public function getEntryMode()
    {
        return $this->getConfigData('card_info_mode');
    }

    protected function getRestApiFormTokenData($quote)
    {
        $amount = $quote->getGrandTotal();

        // Currency.
        $currency = \Lyranetwork\Payzen\Model\Api\Form\Api::findCurrencyByAlphaCode($quote->getQuoteCurrencyCode());
        if (! $currency) {
            // If currency is not supported, use base currency.
            $currency = \Lyranetwork\Payzen\Model\Api\Form\Api::findCurrencyByAlphaCode($quote->getBaseCurrencyCode());

            // ... and order total in base currency.
            $amount = $quote->getBaseGrandTotal();
        }

        if (! $currency) {
            $this->dataHelper->log('Cannot create a form token. Unsupported currency passed.');
            return false;
        }

        // Check if capture_delay and validation_mode are overriden in standard submodule.
        $captureDelay = is_numeric($this->getConfigData('capture_delay')) ? $this->getConfigData('capture_delay') :
            $this->dataHelper->getCommonConfigData('capture_delay');

        $validationMode = ($this->getConfigData('validation_mode') !== '-1') ? $this->getConfigData('validation_mode') :
            $this->dataHelper->getCommonConfigData('validation_mode');

        // Activate 3DS?
        $strongAuth = 'AUTO';
        $threedsMinAmount = $this->dataHelper->getCommonConfigData('threeds_min_amount');
        if ($threedsMinAmount && $quote->getTotalDue() < $threedsMinAmount) {
            $strongAuth = 'DISABLED';
        }

        $billingAddress = $quote->getBillingAddress();

        // Reserve order ID and save quote.
        $quote->reserveOrderId()->save();

        $data = [
            'orderId' => $quote->getReservedOrderId(),
            'customer' => [
                'email' => $quote->getCustomerEmail(),
                'reference' => $quote->getCustomer()->getId(),
                'billingDetails' => [
                    'language' => strtoupper($this->getPaymentLanguage()),
                    'title' => $billingAddress->getPrefix() ? $billingAddress->getPrefix() : null,
                    'firstName' => $billingAddress->getFirstname(),
                    'lastName' => $billingAddress->getLastname(),
                    'address' => implode(' ', $billingAddress->getStreet()),
                    'zipCode' => $billingAddress->getPostcode(),
                    'city' => $billingAddress->getCity(),
                    'state' => $billingAddress->getRegion(),
                    'phoneNumber' => $billingAddress->getTelephone(),
                    'cellPhoneNumber' => $billingAddress->getTelephone(),
                    'country' => $billingAddress->getCountryId()
                ]
            ],
            'transactionOptions' => [
                'cardOptions' => [
                    'captureDelay' => $captureDelay,
                    'manualValidation' => $validationMode ? 'YES' : 'NO',
                    'paymentSource' => 'EC'
                ]
            ],
            'contrib' =>  $this->dataHelper->getContribParam(),
            'strongAuthentication' => $strongAuth,
            'currency' => $currency->getAlpha3(),
            'amount' => $currency->convertAmountToInteger($amount),
            'metadata' => [
                'quote_id' => $quote->getId()
            ]
        ];

        // Set shipping info.
        if (($shippingAddress = $quote->getShippingAddress()) && is_object($shippingAddress)) {
            $data['customer']['shippingDetails'] = array(
                'firstName' => $shippingAddress->getFirstname(),
                'lastName' => $shippingAddress->getLastname(),
                'address' => $shippingAddress->getStreetLine(1),
                'address2' => $shippingAddress->getStreetLine(2),
                'zipCode' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity(),
                'state' => $shippingAddress->getRegion(),
                'phoneNumber' => $shippingAddress->getTelephone(),
                'country' => $shippingAddress->getCountryId()
            );
        }

        // Set the maximum attempts number in case of failed payment.
        if ($this->getConfigData('rest_attempts') !== null) {
            $data['transactionOptions']['cardOptions']['retry'] = $this->getConfigData('rest_attempts');
        }

        $customer = $quote->getCustomerId() ? $this->customerRepository->getById($quote->getCustomerId()) : null;

        if ($this->isOneClickActive() && $customer) {
            $data['formAction'] = 'CUSTOMER_WALLET';
        }

        return json_encode($data);
    }

    public function getRestApiFormToken($renew = false)
    {
        $quote = $this->dataHelper->getCheckoutQuote();

        if (! $quote || ! $quote->getId()) {
            $this->dataHelper->log('Cannot create a form token. Empty quote passed.');
            return false;
        }

        // Amount in current order currency.
        if ($quote->getGrandTotal() <= 0) {
            $this->dataHelper->log('Cannot create a form token. Invalid amount passed.');
            return false;
        }

        $params = $this->getRestApiFormTokenData($quote);

        $tokenDataName = \Lyranetwork\Payzen\Helper\Payment::TOKEN_DATA;
        $tokenName = \Lyranetwork\Payzen\Helper\Payment::TOKEN;
        $expireName = \Lyranetwork\Payzen\Helper\Payment::TOKEN_EXPIRE;

        $expireTime = $quote->getPayment()->getAdditionalInformation($expireName);
        if ($renew || ($expireTime && (time() >= $expireTime))) {
            $quote->getPayment()->unsAdditionalInformation($tokenDataName);
            $quote->getPayment()->unsAdditionalInformation($tokenName);
        } else {
            $lastTokenData = $quote->getPayment()->getAdditionalInformation($tokenDataName);
            $lastToken = $quote->getPayment()->getAdditionalInformation($tokenName);

            $tokenData = base64_encode(serialize($params));
            if ($lastToken && $lastTokenData && ($lastTokenData === $tokenData)) {
                // Cart data does not change from last payment attempt, do not re-create payment token.
                $this->dataHelper->log("Cart data did not change since last payment attempt, use last created token for quote #{$quote->getId()}, reserved order ID #{$quote->getReservedOrderId()}.");
                return $lastToken;
            }
        }

        $this->dataHelper->log("Creating form token for quote #{$quote->getId()}, reserved order ID: #{$quote->getReservedOrderId()}"
            . " with parameters: {$params}");

        try {
            // Perform our request.
            $client = new \Lyranetwork\Payzen\Model\Api\Rest\Api(
                $this->dataHelper->getCommonConfigData('rest_url'),
                $this->dataHelper->getCommonConfigData('site_id'),
                $this->restHelper->getPrivateKey()
            );

            $response = $client->post('V4/Charge/CreatePayment', $params);

            if ($response['status'] !== 'SUCCESS') {
                $msg = "Error while creating payment form token for quote #{$quote->getId()}, reserved order ID: #{$quote->getReservedOrderId()}: "
                    . $response['answer']['errorMessage'] . ' (' . $response['answer']['errorCode'] . ').';

                if (isset($response['answer']['detailedErrorMessage']) && ! empty($response['answer']['detailedErrorMessage'])) {
                    $msg .= ' Detailed message: ' . $response['answer']['detailedErrorMessage'] .' (' . $response['answer']['detailedErrorCode'] . ').';
                }

                $this->dataHelper->log($msg, \Psr\Log\LogLevel::WARNING);
                return false;
            } else {
                $this->dataHelper->log("Form token created successfully for quote #{$quote->getId()}, reserved order ID: #{$quote->getReservedOrderId()}.");

                $token = $response['answer']['formToken'];
                $tokenData = base64_encode(serialize($params));

                $quote->getPayment()->setAdditionalInformation($tokenDataName, $tokenData);
                $quote->getPayment()->setAdditionalInformation($tokenName, $token);
                $quote->getPayment()->setAdditionalInformation($expireName, strtotime("+15 minutes", time()));

                $quote->getPayment()->save();

                // Payment form token created successfully.
                return $token;
            }
        } catch (\Exception $e) {
            $this->dataHelper->log($e->getMessage(), \Psr\Log\LogLevel::ERROR);
            return false;
        }
    }

    public function isOneClickActive()
    {
        return $this->getConfigData('oneclick_active');
    }
}
