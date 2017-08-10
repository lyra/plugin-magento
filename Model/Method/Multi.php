<?php
/**
 * PayZen V2-Payment Module version 2.1.1 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2016 Lyra Network and contributors
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\Method;

class Multi extends Payzen
{
    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_MULTI;
    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Multi::class;

    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;

    protected function setExtraFields($order)
    {
        // set payment_src to MOTO for backend payments
        if ($this->dataHelper->isBackend()) {
            $this->payzenRequest->set('payment_src', 'MOTO');
        }

        $info = $this->getInfoInstance();

        // set mutiple payment option
        $option = @unserialize($info->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION));

        $amount = $this->payzenRequest->get('amount');
        $first = ($option['first'] != '') ? round(($option['first'] / 100) * $amount) : null;
        $this->payzenRequest->setMultiPayment($amount, $first, $option['count'], $option['period']);
        $this->payzenRequest->set('contracts', ($option['contract']) ? 'CB=' . $option['contract'] : null);

        $this->dataHelper->log('Multiple payment configuration is ' . $this->payzenRequest->get('payment_config'));
    }

    protected function sendPaypalFields()
    {
        $cards = explode(',', $this->dataHelper->getCommonConfigData('payment_cards'));
        return in_array('', $cards) /* All cards */ || in_array('PAYPAL', $cards) || in_array('PAYPAL_SB', $cards);
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

        // load option informations
        $option = $this->getOption($payzenData->getData('payzen_multi_option'));
        $info->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION, serialize($option));

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
            return count($options) > 0;
        }

        return true;
    }

    /**
     * Return available payment options to be displayed on payment method list page.
     *
     * @param double $amount a given amount
     * @return array[string][array] An array "$code => $option" of availables options
     */
    public function getAvailableOptions($amount = null)
    {
        $configOptions = unserialize($this->getConfigData('multi_payment_options'));

        $options = [];
        if (is_array($configOptions) && !empty($configOptions)) {
            foreach ($configOptions as $code => $value) {
                if (empty($value)) {
                    continue;
                }

                if ((!$amount || !$value['minimum'] || $amount > $value['minimum'])
                    && (!$amount || !$value['maximum'] || $amount < $value['maximum'])) {
                    // option will be available
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

        if ($code && $options[$code]) {
            return $options[$code];
        } else {
            return false;
        }
    }
}
