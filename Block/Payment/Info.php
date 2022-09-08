<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Payment;

use Lyranetwork\Payzen\Model\Api\Form\Response as PayzenResponse;

class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Lyranetwork_Payzen::payment/info.phtml';

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory
     */
    protected $trsCollectionFactory;

    /**
     * @var \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\ContactSupport
     */
    protected $supportBlock;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory $trsCollectionFactory
     * @param \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\ContactSupport $supportBlock
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory $trsCollectionFactory,
        \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\ContactSupport $supportBlock,
        array $data = []
    ) {
        $this->localeResolver = $localeResolver;
        $this->trsCollectionFactory = $trsCollectionFactory;
        $this->supportBlock = $supportBlock;

        parent::__construct($context, $data);
    }

    public function getResultDescHtml()
    {
        $allResults = @unserialize(
            $this->getInfo()->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::ALL_RESULTS)
        );

        if (! is_array($allResults) || empty($allResults)) {
            // Description is stored as litteral string.
            return $this->getInfo()->getCcStatusDescription();
        } else {
            // Description is stored as serialized array.
            $keys = [
                'result',
                'auth_result',
                'warranty_result'
            ];

            $labels = [];

            $restError = $this->getInfo()->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::REST_ERROR_MESSAGE);
            if ($restError) {
                $labels[] = $restError;
                unset($keys[0]);
            }

            foreach ($keys as $key) {
                $label = $this->translate($allResults[$key], $key, true);

                if (! $label) {
                    continue;
                }

                if (($key === 'result') && ((int)$allResults[$key] === 30)) { // Append form error if any.
                    $label .= ' ' . PayzenResponse::extraMessage($allResults['extra_result']);
                }

                $labels[] = $label;
            }

            return implode('<br />', $labels);
        }
    }

    public function getPaymentDetailsHtml($backend = true)
    {
        $html = '';
        $payment = $this->getInfo();

        $html .= '<b>' . __('Means of payment') . ': </b>' . $payment->getCcType();

        if ($backend) {
            $userChoice = $payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::BRAND_USER_CHOICE);
            if ($userChoice === true) {
                $html .= ' ' . __('(card brand chosen by buyer)');
            } elseif ($userChoice === false) {
                $html .= ' ' . __('(default card brand used)');
            }

            $html .= '<br />';

            $html .= '<b>' . __('Card Number') . ': </b>' . $payment->getCcNumberEnc();
            $html .= '<br />';

            $expiry = '';
            if ($payment->getCcExpMonth() && $payment->getCcExpYear()) {
                $expiry = str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT) . ' / ' . $payment->getCcExpYear();
            }

            $html .= '<b>' . __('Expiration Date') . ': </b>' . $expiry;

            $html .= '<hr />';
        }

        return $html;
    }

    public function getMultiPaymentDetailsHtml($backend = true)
    {
        $collection = $this->trsCollectionFactory->create();
        $collection->addPaymentIdFilter($this->getInfo());
        $collection->load();

        $userChoice = $this->getInfo()->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::BRAND_USER_CHOICE);

        $html = '';

        foreach ($collection as $item) {
            $html .= '<hr />';

            $sequenceNumber = substr($item->getTxnId(), strpos($item->getTxnId(), '-') + 1);

            $html .= '<b>' . __('Sequence Number') . ': </b>' . $sequenceNumber;

            $info = $item->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);
            foreach ($info as $key => $value) {
                if (! $backend && in_array($key, ['Card Number', 'Expiration Date'])) {
                    continue;
                }

                if (! $value) {
                    continue;
                }

                $html .= '<br />';
                $html .= '<b>' . __($key) . ': </b>' . $value;

                if ($backend && ($key === 'Means of payment') && isset($userChoice[$sequenceNumber])) {
                    if ($userChoice[$sequenceNumber] === true) {
                        $html .= ' ' . __('(card brand chosen by buyer)');
                    } elseif ($userChoice[$sequenceNumber] === false) {
                        $html .= ' ' . __('(default card brand used)');
                    }
                }
            }
        }

        if ($backend) {
            $html .= '<hr />';
        }

        return $html;
    }

    public function translate($code, $type, $appendCode = false)
    {
        $lang = strtolower(substr($this->localeResolver->getLocale(), 0, 2));
        return PayzenResponse::translate($code, $type, $lang, $appendCode);
    }

    public function getStoreInfo($order)
    {
        return $this->supportBlock->getStoreInfo($order);
    }

    public function sendMailUrl()
    {
        return $this->supportBlock->sendMailUrl();
    }
}
