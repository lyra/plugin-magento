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
     *
     * @var \Lyranetwork\Payzen\Model\System\Config\Source\SepaAvailableCountry
     */
    protected $sepaCountries;

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
        \Lyranetwork\Payzen\Model\System\Config\Source\SepaAvailableCountry $sepaCountries,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
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
        // Override with SEPA payment card.
        $this->payzenRequest->set('payment_cards', 'SDD');
        $this->payzenRequest->set('page_action', $this->getConfigData('mandate_mode'));
    }

    public function canUseForCountry($country)
    {
        $availableCountries = $this->sepaCountries->getCountryCodes();

        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));
        }

        return in_array($country, $availableCountries);
    }
}
