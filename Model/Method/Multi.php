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

class Multi extends Payzen
{
    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_MULTI;
    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Multi::class;

    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;

    /**
     * @var \Lyranetwork\Payzen\Model\System\Config\Source\MultiPaymentCard
     */
    protected $multiCardPayment;

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
     * @param  \Magento\Framework\App\Response\Http $redirect
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     * @param \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Module\Dir\Reader $dirReader
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Lyranetwork\Payzen\Model\System\Config\Source\MultiPaymentCard $multiCardPayment
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
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Module\Dir\Reader $dirReader,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Lyranetwork\Payzen\Model\System\Config\Source\MultiPaymentCard $multiCardPayment,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->multiCardPayment = $multiCardPayment;

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
        // Set payment_src to MOTO for backend payments.
        if ($this->dataHelper->isBackend()) {
            $this->payzenRequest->set('payment_src', 'MOTO');
            $this->payzenRequest->set('return_mode', 'GET'); // Temporary workaround, TODO
        }

        $info = $this->getInfoInstance();

        if ($this->isLocalCcType() && $info->getCcType()) {
            $this->payzenRequest->set('payment_cards', $info->getCcType());
        } else {
            // Payment_cards is given as csv by magento.
            $paymentCards = $this->dataHelper->explode(',', $this->getConfigData('payment_cards'));
            $paymentCards = in_array('', $paymentCards) ? '' : implode(';', $paymentCards);

            $this->payzenRequest->set('payment_cards', $paymentCards);
        }

        // Set mutiple payment option.
        $option = @unserialize($info->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION));

        $amount = $this->payzenRequest->get('amount');
        $first = ! empty($option['first']) ? round(($option['first'] / 100) * $amount) : null;
        $this->payzenRequest->setMultiPayment($amount, $first, $option['count'], $option['period']);
        $this->payzenRequest->set('contracts', ($option['contract']) ? 'CB=' . $option['contract'] : null);

        $this->dataHelper->log('Multiple payment configuration is ' . $this->payzenRequest->get('payment_config')
            . ', order ID: #' . $order->getIncrementId());
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
        $option = $this->getOption($payzenData->getData('payzen_multi_option'));

        $info->setCcType($payzenData->getData('payzen_multi_cc_type'))
            ->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION, serialize($option));

        return $this;
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
        if ($amount) {
            $options = $this->getAvailableOptions($amount);
            return ! empty($options);
        }

        return true;
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
        $configOptions = $this->dataHelper->unserialize($this->getConfigData('multi_payment_options'));

        $options = [];
        if (is_array($configOptions) && ! empty($configOptions)) {
            foreach ($configOptions as $code => $value) {
                if (empty($value)) {
                    continue;
                }

                if ((! $amount || ! $value['minimum'] || $amount > $value['minimum']) &&
                     (! $amount || ! $value['maximum'] || $amount < $value['maximum'])) {
                    // Option will be available.
                    $options[$code] = $value;
                }
            }
        }

        return $options;
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
     * Return available card types.
     *
     * @return string
     */
    public function getAvailableCcTypes()
    {
        if (! $this->isLocalCcType()) {
            return null;
        }

        // All cards.
        $allCards = $this->multiCardPayment->toOptionArray();

        // Selected cards from module configuration.
        $cards = $this->getConfigData('payment_cards');
        $cards = ! empty($cards) ? explode(',', $cards) : [];

        $availCards = [];
        foreach ($allCards as $card) {
            if (! $card['value']) {
                continue;
            }

            if (empty($cards) || in_array($card['value'], $cards)) {
                $availCards[$card['value']] = $card['simple_label'];
            }
        }

        return $availCards;
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
     * Return card selection mode.
     *
     * @return int
     */
    public function getEntryMode()
    {
        return $this->getConfigData('card_info_mode');
    }
}
