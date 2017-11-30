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

class Oney extends Payzen
{

    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_ONEY;

    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Oney::class;

    /**
     *
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

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
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
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
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        $this->pricingHelper = $pricingHelper;

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
        $testMode = $this->payzenRequest->get('ctx_mode') == 'TEST';

        // override with FacilyPay Oney payment cards
        $this->payzenRequest->set('payment_cards', $testMode ? 'ONEY_SANDBOX' : 'ONEY');

        // set choosen option if any
        $info = $this->getInfoInstance();
        $option = @unserialize($info->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::ONEY_OPTION));
        if ($option && is_array($option)) {
            $this->payzenRequest->set('payment_option_code', $option['code']);
        }
    }

    protected function sendOneyFields()
    {
        return true;
    }

    /**
     * Assign data to info model instance.
     *
     * @param \Magento\Framework\DataObject $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        // reset payment method specific data
        $this->resetData();

        parent::assignData($data);

        $info = $this->getInfoInstance();

        $payzenData = $this->extractPayzenData($data);
        $option = $this->getOption($payzenData->getData('payzen_oney_option'));

        // init all payment data
        $info->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::ONEY_OPTION, serialize($option));

        return $this;
    }

    /**
     * Get available payment options for the current cart amount.
     *
     * @param double $amount
     *            a given amount
     * @return array[string][array] an array "$code => $option" of available options
     */
    public function getPaymentOptions($amount)
    {
        $configOptions = $this->dataHelper->unserialize($this->getConfigData('oney_payment_options'));
        if (! is_array($configOptions) || empty($configOptions)) {
            return false;
        }

        $options = [];
        foreach ($configOptions as $code => $value) {
            if (empty($value)) {
                continue;
            }

            if ((! $value['minimum'] || ($amount > $value['minimum'])) &&
                 (! $value['maximum'] || ($amount < $value['maximum']))) {
                // option will be available
                $c = is_numeric($value['count']) ? $value['count'] : 1;
                $r = is_numeric($value['rate']) ? $value['rate'] : 0;
                $a = $this->pricingHelper->currency($amount * pow(1 + $r / 100, $c - 1) / $c, true, false);

                // get final option description
                $search = [
                    '%c',
                    '%r',
                    '%a'
                ];
                $replace = [
                    $c,
                    $r . ' %',
                    $a
                ];
                $value['label'] = str_replace($search, $replace, $value['label']); // label to display on payment page

                $options[$code] = $value;
            }
        }

        return $options;
    }

    private function getOption($code)
    {
        $info = $this->getInfoInstance();
        if ($info instanceof \Magento\Sales\Model\Order\Payment) {
            $amount = $info->getOrder()->getBaseGrandTotal();
        } else {
            $amount = $info->getQuote()->getBaseGrandTotal();
        }

        $options = $this->getPaymentOptions($amount);
        if ($code && isset($options[$code])) {
            return $options[$code];
        } else {
            return false;
        }
    }

    /**
     * Return true if the method can be used at this time.
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $checkResult = parent::isAvailable($quote);

        if (! $checkResult || ! is_object($quote) || ! $quote->getCustomerId()) {
            return false;
        }

        if (! preg_match(\Lyranetwork\Payzen\Helper\Checkout::CUST_ID_REGEX, $quote->getCustomerId())) {
            // customer id doesn't match FacilyPay Oney rules

            $msg = 'Customer ID "%s" does not match PayZen specifications.';
            $msg .= ' The regular expression for this field is %s. FacilyPay Oney payment mean cannot be used.';
            $this->dataHelper->log(
                sprintf($msg, $quote->getCustomerId(), \Lyranetwork\Payzen\Helper\Checkout::CUST_ID_REGEX),
                \Psr\Log\LogLevel::WARNING
            );
            return false;
        }

        if (! $quote->getReservedOrderId()) {
            $quote->reserveOrderId(); // guess order id
        }

        if (! preg_match(\Lyranetwork\Payzen\Helper\Checkout::ORDER_ID_REGEX, $quote->getReservedOrderId())) {
            // order id doesn't match FacilyPay Oney rules

            $msg = 'The order ID "%s" does not match PayZen specifications.';
            $msg .= 'The regular expression for this field is %s. FacilyPay Oney payment mean cannot be used.';
            $this->dataHelper->log(
                sprintf($msg, $quote->getReservedOrderId(), \Lyranetwork\Payzen\Helper\Checkout::ORDER_ID_REGEX),
                \Psr\Log\LogLevel::WARNING
            );
            return false;
        }

        foreach ($quote->getAllItems() as $item) {
            // check to avoid sending the whole hierarchy of a configurable product
            if ($item->getParentItem()) {
                continue;
            }

            if (! preg_match(\Lyranetwork\Payzen\Helper\Checkout::PRODUCT_REF_REGEX, $item->getProductId())) {
                // product id doesn't match FacilyPay Oney rules

                $msg = 'Product reference "%s" does not match PayZen specifications.';
                $msg .= 'The regular expression for this field is %s. FacilyPay Oney payment mean cannot be used.';
                $this->dataHelper->log(
                    sprintf($msg, $item->getProductId(), \Lyranetwork\Payzen\Helper\Checkout::PRODUCT_REF_REGEX),
                    \Psr\Log\LogLevel::WARNING
                );
                return false;
            }
        }

        if (! $quote->isVirtual() && $quote->getShippingAddress()->getShippingMethod()) {
            $method = $quote->getShippingAddress()->getShippingMethod();

            $shippingMethod = $this->checkoutHelper->toOneyCarrier($method);
            if (! $shippingMethod) {
                // selected shipping method is not mapped in configuration panel

                $this->dataHelper->log(
                    "Shipping method \"{$method}\" is not correctly mapped in module configuration panel. Module is not displayed.",
                    \Psr\Log\LogLevel::WARNING
                );
                return false;
            }
        }

        return $checkResult;
    }
}
