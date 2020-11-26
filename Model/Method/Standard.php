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
            $productMetadata,
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
            $paymentCards = explode(',', $this->getConfigData('payment_cards'));
            $paymentCards = in_array('', $paymentCards) ? '' : implode(';', $paymentCards);

            $this->payzenRequest->set('payment_cards', $paymentCards);
        }

        // Set payment_src to MOTO for backend payments.
        if ($this->dataHelper->isBackend()) {
            $this->payzenRequest->set('payment_src', 'MOTO');
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
        $allCards = \Lyranetwork\Payzen\Model\Api\PayzenApi::getSupportedCardTypes();

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

    public function getRestApiFormToken()
    {
        $quote = $this->dataHelper->getCheckoutQuote();

        if (! $quote || ! $quote->getId()) {
            $this->dataHelper->log('Cannot create form token. Empty quote passed.', \Psr\Log\LogLevel::WARNING);
            return false;
        }

        // Amount in current order currency.
        $amount = $quote->getGrandTotal();

        // Currency.
        $currency = \Lyranetwork\Payzen\Model\Api\PayzenApi::findCurrencyByAlphaCode($quote->getOrderCurrencyCode());
        if ($currency == null) {
            // If currency is not supported, use base currency.
            $currency = \Lyranetwork\Payzen\Model\Api\PayzenApi::findCurrencyByAlphaCode($quote->getBaseCurrencyCode());

            // ... and order total in base currency.
            $amount = $quote->getBaseGrandTotal();
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

        // Version.
        $cmsParam = $this->dataHelper->getCommonConfigData('cms_identifier') . '_'
            . $this->dataHelper->getCommonConfigData('plugin_version');
        $cmsVersion = $this->productMetadata->getVersion(); // Will return the Magento version.

        $billingAddress = $quote->getBillingAddress();

        if (! $quote->getReservedOrderId()) {
            // Reserve order ID and save quote.
            $quote->reserveOrderId()->save();
        }

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
            'contrib' => $cmsParam . '/' . $cmsVersion . '/' . PHP_VERSION,
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
                'state' => $shippingAddress->getregion(),
                'phoneNumber' => $shippingAddress->gettelephone(),
                'country' => $shippingAddress->getCountryId()
            );
        }

        // Set the maximum attempts number in case of failed payment.
        if ($this->getConfigData('rest_attempts')) {
            $data['transactionOptions']['cardOptions']['retry'] = $this->getConfigData('rest_attempts');
        }

        try {
            // Perform our request.
            $client = new \Lyranetwork\Payzen\Model\Api\PayzenRest(
                $this->dataHelper->getCommonConfigData('rest_url'),
                $this->dataHelper->getCommonConfigData('site_id'),
                $this->restHelper->getPrivateKey()
            );

            $response = $client->post('V4/Charge/CreatePayment', json_encode($data));

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

                // Payment form token created successfully.
                return $response['answer']['formToken'];
            }
        } catch (\Exception $e) {
            $this->dataHelper->log($e->getMessage(), \Psr\Log\LogLevel::ERROR);
            return false;
        }
    }

    public function isOneClickActive()
    {
        // 1-Click enabled and not payment by embedded fields (REST API).
        if (! $this->isRestMode() && $this->getConfigData('oneclick_active')) {
            return true;
        }

        return false;
    }
}
