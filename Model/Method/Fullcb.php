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

class Fullcb extends Payzen
{
    const FULLCB_THREE_TIMES_MAX_FEES = 9;
    const FULLCB_FOUR_TIMES_MAX_FEES = 12;

    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_FULLCB;
    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Fullcb::class;

    protected $_canUseInternal = false;

    protected $currencies = ['EUR'];

    protected function setExtraFields($order)
    {
        // Override with Full CB specific params.
        $this->payzenRequest->set('cust_status', 'PRIVATE');
        $this->payzenRequest->set('validation_mode', '0');
        $this->payzenRequest->set('capture_delay', '0');

        // Override with selected Full CB payment card.
        $info = $this->getInfoInstance();

        // Set choosen card if any.
        if ($info->getCcType()) {
            $this->payzenRequest->set('payment_cards', $info->getCcType());
        } else {
            $this->payzenRequest->set('payment_cards', 'FULLCB3X;FULLCB4X');
        }
    }

    /**
     * Assign data to info model instance.
     *
     * @param \Magento\Framework\DataObject $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $info = $this->getInfoInstance();

        $payzenData = $this->extractPaymentData($data);

        // Load option informations.
        $option = $payzenData->getData('payzen_fullcb_option');
        if ($option) {
            $info->setCcType($option)->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::FULLCB_OPTION, $option);
        }

        return $this;
    }

    /**
     * Return true if the method can be used at this time.
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(?\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (! parent::isAvailable($quote)) {
            return false;
        }

        $amount = $quote ? $quote->getBaseGrandTotal() : null;
        if ($amount) {
            if ($this->getConfigData('enable_payment_options')) {
                $options = $this->getAvailableOptions($amount);
                return ! empty($options);
            }
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
        $configOptions = $this->dataHelper->unserialize($this->getConfigData('payment_options'));
        if (! is_array($configOptions) || empty($configOptions)) {
            return [];
        }

        $optionCount = [
            'FULLCB3X' => 3,
            'FULLCB4X' => 4
        ];

        $availOptions = [];
        foreach ($configOptions as $code => $option) {
            if (empty($option)) {
                continue;
            }

            if ((! $amount || ! $option['amount_min'] || $amount > $option['amount_min'])
                && (! $amount || ! $option['amount_max'] || $amount < $option['amount_max'])) {
                // Compute some fields.
                $count = (int) $optionCount[$code];
                $rate = (float) $option['rate'];

                $max_fees = $option['cap'];
                if (! $max_fees) {
                    switch ($count) {
                        case 3:
                            $max_fees = self::FULLCB_THREE_TIMES_MAX_FEES;
                            break;
                        case 4:
                            $max_fees = self::FULLCB_FOUR_TIMES_MAX_FEES;
                            break;
                        default:
                            $max_fees = null;
                            break;
                    }
                }

                $payment = round($amount / $count, 2);

                $fees = round($amount * $rate / 100, 2);
                if ($max_fees) {
                    $fees = min($fees, $max_fees);
                }

                $first = $amount - ($payment * ($count - 1)) + $fees;

                $option['order_amount'] = (float) $amount;
                $option['first_payment'] = $first;
                $option['monthly_payment'] = $payment;
                $option['total_amount'] = $amount + $fees;
                $option['fees'] = $fees;
                $option['count'] = $count;

                $availOptions[$code] = $option;
            }
        }

        return $availOptions;
    }
}
