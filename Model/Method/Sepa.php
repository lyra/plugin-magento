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

class Sepa extends Payzen
{
    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_SEPA;
    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Sepa::class;

    protected $_canUseInternal = false;

    protected $currencies = ['EUR'];

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Lyranetwork\Payzen\Model\System\Config\Source\SepaAvailableCountry
     */
    protected $sepaCountries;

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
     * @param \Lyranetwork\Payzen\Model\System\Config\Source\SepaAvailableCountry $sepaCountries
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
        \Lyranetwork\Payzen\Model\System\Config\Source\SepaAvailableCountry $sepaCountries,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->sepaCountries = $sepaCountries;

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
        // Override with SEPA payment card.
        $this->payzenRequest->set('payment_cards', 'SDD');

        if ($this->isOneClickActive() && $order->getCustomerId()) {
            // Customer logged-in.
            $info = $this->getInfoInstance();

            $customer = $this->customerRepository->getById($order->getCustomerId());

            if ($customer->getCustomAttribute('payzen_sepa_identifier') && $this->customerSession->getValidSepaAlias()) {
                // Customer has an identifier.
                $this->payzenRequest->set('identifier', $customer->getCustomAttribute('payzen_sepa_identifier')->getValue());

                if (! $info->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::SEPA_IDENTIFIER)) {
                    // Customer choose to not use alias.
                    $this->payzenRequest->set('page_action', 'REGISTER_UPDATE_PAY');
                }
            } else {
                // Bank data acquisition on payment page, let's ask customer for data registration.
                $this->dataHelper->log('Customer ' . $customer->getEmail() .
                    ' will be asked for card data registration on payment page.');
                $this->payzenRequest->set('page_action', 'ASK_REGISTER_PAY');
            }

            $this->customerSession->unsValidSepaAlias();
        } else {
            $this->payzenRequest->set('page_action', $this->getMandateMode());
        }
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
        if (! $this->getCurrentCustomer() || ! ($identifier = $this->getCurrentCustomer()->getCustomAttribute('payzen_sepa_identifier'))) {
            return false;
        }

        try {
            $aliasEnabled = $this->restHelper->checkIdentifier($identifier->getValue(), $this->getCurrentCustomer()->getEmail());
        }  catch (\Exception $e) {
            $this->dataHelper->log(
                "Saved identifier for customer {$this->getCurrentCustomer()->getEmail()} couldn't be verified on gateway. Error occurred: {$e->getMessage()}",
                \Psr\Log\LogLevel::ERROR
            );

            // Unable to validate alias online, we cannot disable feature.
            $aliasEnabled = true;
        }

        $this->customerSession->setValidSepaAlias($aliasEnabled);
        return $aliasEnabled;
    }

    public function isOneClickActive()
    {
        // 1-Click enabled and SEPA direct debit mode is REGISTER_PAY.
        if (($this->getConfigData('mandate_mode') === 'REGISTER_PAY') && $this->getConfigData('oneclick_active')) {
            return true;
        }

        return false;
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

        // Whether to do a payment by identifier.
        $info->setAdditionalInformation(
            \Lyranetwork\Payzen\Helper\Payment::SEPA_IDENTIFIER,
            $payzenData->getData('payzen_sepa_use_identifier')
        );

        return $this;
    }

    public function canUseForCountry($country)
    {
        $availableCountries = \Lyranetwork\Payzen\Model\Api\Form\Api::getSepaCountries();

        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = $this->dataHelper->explode(',', $this->getConfigData('specificcountry'));
        }

        return in_array($country, $availableCountries);
    }

    /**
     * Return Sepa mandate mode.
     *
     * @return string
     */
    public function getMandateMode()
    {
        return $this->getConfigData('mandate_mode');
    }
}
