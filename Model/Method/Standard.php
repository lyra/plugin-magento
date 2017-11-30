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

class Standard extends Payzen
{

    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_STANDARD;

    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Standard::class;

    protected $_canSaveCc = true;

    /**
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

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
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Payment $paymentHelper,
        \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Module\Dir\Reader $dirReader,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
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
            $dataHelper,
            $paymentHelper,
            $checkoutHelper,
            $productMetadata,
            $messageManager,
            $dirReader,
            $dataObjectFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    protected function setExtraFields($order)
    {
        $info = $this->getInfoInstance();

        if ($this->isLocalCcType() || $this->isLocalCcInfo()) {
            // set payment_cards
            $this->payzenRequest->set('payment_cards', $info->getCcType());
        } else {
            // payment_cards is given as csv by magento
            $paymentCards = explode(',', $this->getConfigData('payment_cards'));
            $paymentCards = in_array('', $paymentCards) ? '' : implode(';', $paymentCards);

            $this->payzenRequest->set('payment_cards', $paymentCards);
        }

        // set payment_src to MOTO for backend payments
        if ($this->dataHelper->isBackend()) {
            $this->payzenRequest->set('payment_src', 'MOTO');
            return;
        }

        if ($this->isIframeMode()) {
            // iframe enabled
            $this->payzenRequest->set('action_mode', 'IFRAME');

            // enable automatic redirection
            $this->payzenRequest->set('redirect_enabled', '1');
            $this->payzenRequest->set('redirect_success_timeout', '0');
            $this->payzenRequest->set('redirect_error_timeout', '0');

            $returnUrl = $this->payzenRequest->get('url_return');
            $this->payzenRequest->set('url_return', $returnUrl . '?iframe=true');
        }

        if (! $this->getConfigData('oneclick_active') || ! $order->getCustomerId()) {
            $this->setCcInfo();
        } else {
            // 1-Click enabled and customer logged-in
            $customer = $this->customerRepository->getById($this->getCustomerId());

            if ($customer->getData('payzen_identifier') &&
                 $info->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::IDENTIFIER)) {
                // customer has an identifier and wants to use it
                $this->dataHelper->log('Customer ' . $customer->getEmail() . ' has an identifier and chose to use it for payment.');
                $this->payzenRequest->set('identifier', $customer->getData('payzen_identifier'));
            } else {
                if ($this->isLocalCcInfo() &&
                     $info->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::CC_REGISTER)) {
                    // customer wants to register card data

                    if ($customer->getData('payzen_identifier')) {
                        // customer has already an identifier
                        $this->dataHelper->log('Customer ' . $customer->getEmail() .
                             ' has an identifier and chose to update it with new card info.');
                        $this->payzenRequest->set('identifier', $customer->getData('payzen_identifier'));
                        $this->payzenRequest->set('page_action', 'REGISTER_UPDATE_PAY');
                    } else {
                        $this->dataHelper->log('Customer ' . $customer->getEmail() .
                             ' has not identifier and chose to register his card info.');
                        $this->payzenRequest->set('page_action', 'REGISTER_PAY');
                    }
                } elseif (! $this->isLocalCcInfo()) {
                    // bank data acquisition on payment page, let's ask customer for data registration
                    $this->dataHelper->log('Customer ' . $customer->getEmail() .
                         ' will be asked for card data registration on payment page.');
                    $this->payzenRequest->set('page_action', 'ASK_REGISTER_PAY');
                }

                $this->setCcInfo();
            }
        }
    }

    private function setCcInfo()
    {
        if (! $this->isLocalCcInfo()) {
            return;
        }

        $info = $this->getInfoInstance();

        $this->payzenRequest->set('cvv', $info->getCcCid());
        $this->payzenRequest->set('card_number', $info->getCcNumber());
        $this->payzenRequest->set('expiry_year', $info->getCcExpYear());
        $this->payzenRequest->set('expiry_month', $info->getCcExpMonth());

        // override action_mode
        $this->payzenRequest->set('action_mode', 'SILENT');
    }

    protected function sendOneyFields()
    {
        $oneyContract = $this->dataHelper->getCommonConfigData('oney_contract');
        if (! $oneyContract) {
            return false;
        }

        $cards = explode(',', $this->getConfigData('payment_cards'));
        return in_array('', $cards) /* All cards */ || in_array('ONEY', $cards) || in_array('ONEY_SANDBOX', $cards);
    }

    /**
     * Return available card types.
     *
     * @return array[string][array]
     */
    public function getAvailableCcTypes()
    {
        // all cards
        $allCards = \Lyranetwork\Payzen\Model\Api\PayzenApi::getSupportedCardTypes();

        // selected cards from module configuration
        $cards = $this->getConfigData('payment_cards');

        if (! empty($cards)) {
            $cards = explode(',', $cards);
        } else {
            $cards = array_keys($allCards);
        }

        if (! $this->sendOneyFields()) {
            $cards = array_diff($cards, [
                'ONEY',
                'ONEY_SANDBOX'
            ]);
        }

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

        // no 1-Click
        if (! $this->getConfigData('oneclick_active')) {
            return false;
        }

        if ($this->dataHelper->isBackend()) {
            return false;
        }

        // customer not logged in
        if (! $this->customerSession->isLoggedIn()) {
            return false;
        }

        // customer has not PayZen identifier
        $customer = $this->customerSession->getCustomer();
        if (! $customer || ! $customer->getData('payzen_identifier')) {
            return false;
        }

        return true;
    }

    /**
     * Assign data to info model instance.
     *
     * @param array|\Magento\Framework\DataObject $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        // reset payment method specific data
        $this->resetData();

        parent::assignData($data);

        $info = $this->getInfoInstance();

        $payzenData = $this->extractPayzenData($data);

        if ($payzenData->getData('payzen_use_identifier')) {
            // payment by identifier
            $info->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::IDENTIFIER, true);
        } else {
            // set card info
            $info->setCcType($payzenData->getData('payzen_standard_cc_type'))
                ->setCcLast4(substr($payzenData->getData('payzen_standard_cc_number'), - 4))
                ->setCcNumber($payzenData->getData('payzen_standard_cc_number'))
                ->setCcCid($payzenData->getData('payzen_standard_cc_cvv'))
                ->setCcExpMonth($payzenData->getData('payzen_standard_cc_exp_month'))
                ->setCcExpYear($payzenData->getData('payzen_standard_cc_exp_year'))
                // wether to register data
                ->setAdditionalInformation(
                    \Lyranetwork\Payzen\Helper\Payment::CC_REGISTER,
                    $payzenData->getData('payzen_standard_cc_register')
                )
                ->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::IDENTIFIER, false);
        }

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

        return $this->getConfigData('card_info_mode') == 3;
    }

    /**
     * Check if the bank data acquisition on merchant site option is selected.
     *
     * @return bool
     */
    public function isLocalCcInfo()
    {
        if ($this->dataHelper->isBackend()) {
            return false;
        }

        // this mode will disappear
        return false;
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

        return $this->getConfigData('card_info_mode') == 2;
    }
}
