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
Use  Lyranetwork\Payzen\Helper\Checkout;

class Oney extends Payzen
{
    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_ONEY;
    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Oney::class;

    protected $_canUseInternal = false;

    protected $needsCartData = true;
    protected $needsShippingMethodData = true;

    protected $currencies = ['EUR'];

    /**
     * @var \Lyranetwork\Payzen\Model\System\Config\Source\OneyAvailableCountry
     */
    protected $oneyCountries;

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
        \Lyranetwork\Payzen\Model\System\Config\Source\OneyAvailableCountry $oneyCountries,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->oneyCountries = $oneyCountries;

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

        // Get Oney payment option.
        $option = @unserialize($info->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::ONEY_OPTION));

        // Override with Oney option payment mean.
        $this->payzenRequest->set('payment_cards', $option['card_type']);
        $this->payzenRequest->set('payment_option_code', $option['code']);
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

        // Load option informations.
        $option = $this->getOption($payzenData->getData('payzen_oney_option'));
        $info->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::ONEY_OPTION, serialize($option));

        return $this;
    }

    /**
     * Return available payment options to be displayed on payment method list page.
     *
     * @param double $amount
     *            a given amount
     * @return array[string][array] An array "$code => $option" of availables options
     */
    public function getAvailableOptions($amount = null)
    {
        $configOptions = $this->dataHelper->unserialize($this->getConfigData('oney_payment_options'));
        if (! is_array($configOptions) || empty($configOptions)) {
            return [];
        }

        $availableOptions = [];
        foreach ($configOptions as $code => $option) {
            if (empty($option)) {
                continue;
            }

            if ((! $amount || ! $option['minimum'] || $amount > $option['minimum']) &&
                (! $amount || ! $option['maximum'] || $amount < $option['maximum'])) {
                // Option will be available.
                $availableOptions[$code] = $option;
            }
        }

        return $availableOptions;
    }

    private function getOption($code)
    {
        $info = $this->getInfoInstance();
        if ($info instanceof \Mage\Sales\Model\Order\Payment) {
            $amount = $info->getOrder()->getBaseGrandTotal();
        } else {
            $amount = $info->getQuote()->getBaseGrandTotal();
        }

        $options = $this->getAvailableOptions($amount);

        if ($code && isset($options[$code])) {
            return $options[$code];
        }

        return false;
    }

    /**
     * Check whether payment method can be used.
     *
     * @param  Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $checkResult = parent::isAvailable($quote);

        if (! $checkResult || ! $quote) {
            return $checkResult;
        }

        // Check shipping country, billing country is checked in parent::isAvailable method.
        if (! $quote->isVirtual() && $quote->getShippingAddress()
            && ! $this->canUseForCountry($quote->getShippingAddress()->getCountry())) {
            return false;
        }

        if ($quote->getCustomerId() && ! preg_match(Checkout::CUST_ID_REGEX, $quote->getCustomerId())) {
            // Customer id doesn't match Oney rules.
            $msg = 'Customer ID "%s" does not match gateway specifications. The regular expression for this field is %s. Oney means of payment cannot be used.';
            $this->dataHelper->log(sprintf($msg, $quote->getCustomerId(), Checkout::CUST_ID_REGEX), \Psr\Log\LogLevel::WARNING);
            return false;
        }

        // Reserve order ID and save quote.
        $quote->reserveOrderId();

        if (! preg_match(Checkout::ORDER_ID_REGEX, $quote->getReservedOrderId())) {
            // Order id doesn't match Oney rules.
            $msg = 'The order ID "%s" does not match gateway specifications. The regular expression for this field is %s. Oney means of payment cannot be used.';
            $this->dataHelper->log(sprintf($msg, $quote->getReservedOrderId(), Checkout::ORDER_ID_REGEX), \Psr\Log\LogLevel::WARNING);
            return false;
        }

        foreach ($quote->getAllItems() as $item) {
            // Check to avoid sending the whole hierarchy of a configurable product.
            if ($item->getParentItem()) {
                continue;
            }

            if (! preg_match(Checkout::PRODUCT_REF_REGEX, $item->getProductId())) {
                // Product id doesn't match Oney rules.
                $msg = 'Product reference "%s" does not match gateway specifications. The regular expression for this field is %s. Oney means of payment cannot be used.';
                $this->dataHelper->log(sprintf($msg, $item->getProductId(), Checkout::PRODUCT_REF_REGEX), \Psr\Log\LogLevel::WARNING);
                return false;
            }
        }

        if (! $quote->isVirtual() && $quote->getShippingAddress()->getShippingMethod()) {
            $shippingMethod = $this->checkoutHelper->toPayzenCarrier($quote->getShippingAddress()->getShippingMethod());
            if (! $shippingMethod) {
                // Selected shipping method is not mapped in configuration panel.
                $this->dataHelper->log('Shipping method "' . $quote->getShippingAddress()->getShippingMethod() . '" is not correctly mapped in module configuration panel. Module is not displayed.', \Psr\Log\LogLevel::WARNING);
                return false;
            }
        }

        // Clear error message.
        $this->checkoutHelper->clearErrorMsg();

        $this->checkoutHelper->checkAddressValidity($quote->getShippingAddress());
        $this->checkoutHelper->checkAddressValidity($quote->getBillingAddress());

        return true;
    }

    public function canUseForCountry($country)
    {
        $availableCountries = $this->oneyCountries->getCountryCodes();

        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = $this->dataHelper->explode(',', $this->getConfigData('specificcountry'));
        }

        return in_array($country, $availableCountries);
    }
}
