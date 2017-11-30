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
namespace Lyranetwork\Payzen\Block\Payment;

use Lyranetwork\Payzen\Model\Api\PayzenResponse;

class Info extends \Magento\Payment\Block\Info
{

    /**
     *
     * @var string
     */
    protected $_template = 'Lyranetwork_Payzen::payment/info.phtml';

    /**
     *
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory
     */
    protected $trsCollectionFactory;

    /**
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory $trsCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory $trsCollectionFactory,
        array $data = []
    ) {
        $this->localeResolver = $localeResolver;
        $this->trsCollectionFactory = $trsCollectionFactory;

        parent::__construct($context, $data);
    }

    public function getResultDescHtml()
    {
        $allResults = @unserialize(
            $this->getInfo()->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::ALL_RESULTS)
        );

        if (! is_array($allResults) || empty($allResults)) {
            // description is stored as litteral string
            return $this->getInfo()->getCcStatusDescription();
        } else {
            // description is stored as serialized array
            $keys = [
                'result',
                'auth_result',
                'warranty_result'
            ];

            $labels = [];
            foreach ($keys as $key) {
                $label = $this->translate($allResults[$key], $key, true);
                if (! $label) {
                    continue;
                }

                if ($key === 'result' && $allResults[$key] == '30') { // append form error if any
                    $label .= ' ' . PayzenResponse::extraMessage($allResults['extra_result']);
                }

                $labels[] = $label;
            }

            return implode('<br />', $labels);
        }
    }

    public function getPaymentDetailsHtml()
    {
        $html = '';
        $payment = $this->getInfo();

        $html .= __('Payment Mean') . ' : ' . $payment->getCcType();
        $html .= '<br />';

        $html .= __('Credit Card Number') . ' : ' . $payment->getCcNumberEnc();
        $html .= '<br />';

        $expiry = '';
        if ($payment->getCcExpMonth() && $payment->getCcExpYear()) {
            $expiry = str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT) . ' / ' . $payment->getCcExpYear();
        }
        $html .= __('Expiration Date') . ' : ' . $expiry;
        $html .= '<br />';

        $html .= __('3-DS Authentication') . ' : ';
        if ($payment->getCcSecureVerify()) {
            $html .= __('YES');
            $html .= '<br />';
            $html .= __('3-DS Certificate') . ' : ' . $payment->getCcSecureVerify();
        } else {
            $html .= __('NO');
        }

        return $html;
    }

    public function getTransactionsDetailsHtml()
    {
        $collection = $this->trsCollectionFactory->create();
        $collection->addPaymentIdFilter($this->getInfo());
        $collection->load();

        $html = '';

        foreach ($collection as $item) {
            $html .= '<hr />';

            $html .= __('Sequence Number') . ' : ' . substr($item->getTxnId(), strpos($item->getTxnId(), '-') + 1);
            $html .= '<br />';

            $info = $item->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);
            foreach ($info as $key => $value) {
                $html .= __($key) . ' : ' . $value;
                $html .= '<br />';
            }
        }

        return $html;
    }

    public function translate($code, $type, $appendCode = false)
    {
        $lang = strtolower(substr($this->localeResolver->getLocale(), 0, 2));
        return PayzenResponse::translate($code, $type, $lang, $appendCode);
    }
}
