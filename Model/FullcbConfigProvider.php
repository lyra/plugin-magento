<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model;

class FullcbConfigProvider extends \Lyranetwork\Payzen\Model\PayzenConfigProvider
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper
    ) {
        parent::__construct(
            $storeManager,
            $urlBuilder,
            $dataHelper,
            \Lyranetwork\Payzen\Helper\Data::METHOD_FULLCB
        );

        $this->timezone = $timezone;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = parent::getConfig();

        $options = $this->getAvailableOptions();
        $config['payment'][$this->method->getCode()]['availableOptions'] = ! empty ($options) ? $options: null;

        return $config;
    }

    private function getAvailableOptions()
    {
        $quote = $this->dataHelper->getCheckoutQuote();
        $amount = ($quote && $quote->getId()) ? $quote->getBaseGrandTotal() : null;

        $options = [];
        if ($this->method->getConfigData('enable_payment_options')) {
            foreach ($this->method->getAvailableOptions($amount) as $key => $option) {
                $date = time();
                $firstDate = $this->timezone->formatDate(date('Y-m-d', $date));

                $installmentDates = [];
                for ($i = 0; $i < $option['count'] - 1; $i ++) {
                    $date = strtotime('+30 days', $date);
                    $installmentDates[$i] = $this->timezone->formatDate(date('Y-m-d', $date));
                }

                $options[] = [
                    'key' => $key,
                    'label' => $option['label'],
                    'first_date' => $firstDate,
                    'installment_dates' => $installmentDates,
                    'order_amount' => $this->pricingHelper->currency($option['order_amount' ], true, false),
                    'first_payment' => $this->pricingHelper->currency($option['first_payment'], true, false),
                    'monthly_payment' => $this->pricingHelper->currency($option['monthly_payment'], true, false),
                    'total_amount' => $this->pricingHelper->currency($option['total_amount'], true, false),
                    'fees' =>  $this->pricingHelper->currency($option['fees'], true, false)
                ];
            }
        }

        return $options;
    }
}
