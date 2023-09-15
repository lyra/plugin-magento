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

class FranfinanceConfigProvider extends \Lyranetwork\Payzen\Model\PayzenConfigProvider
{
    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param string $methodCode
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Lyranetwork\Payzen\Helper\Data $dataHelper
    ) {
        parent::__construct(
            $storeManager,
            $urlBuilder,
            $dataHelper,
            \Lyranetwork\Payzen\Helper\Data::METHOD_FRANFINANCE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = parent::getConfig();
        $config['payment'][$this->method->getCode()]['availableOptions'] = $this->getAvailableOptions();

        return $config;
    }

    private function getAvailableOptions()
    {
        $quote = $this->dataHelper->getCheckoutQuote();
        $amount = ($quote && $quote->getId()) ? $quote->getBaseGrandTotal() : null;

        $options = [];
        foreach ($this->method->getAvailableOptions($amount) as $key => $option) {
            // Get final option description.
            $count = substr($option['payment_means'], -2, 1);
            $c = is_numeric($count) ? $count : 1;

            $search = array('%c');
            $replace = array($c);
            $label = str_replace($search, $replace, $option['label'] ? $option['label'] : ''); // Label to display on payment page.

            $options[] = [
                'key' => $key,
                'label' => $label
            ];
        }

        return $options;
    }
}
