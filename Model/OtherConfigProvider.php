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

class OtherConfigProvider extends \Lyranetwork\Payzen\Model\PayzenConfigProvider
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
            \Lyranetwork\Payzen\Helper\Data::METHOD_OTHER
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = parent::getConfig();
        $config['payment'][$this->method->getCode()]['availableOptions'] = $this->getAvailableOptions();
        $config['payment'][$this->method->getCode()]['regroupMode'] = ($this->method->getRegroupMode() == 1);

        return $config;
    }

    private function getAvailableOptions()
    {
        $quote = $this->dataHelper->getCheckoutQuote();

        $options = [];
        foreach ($this->method->getAvailableMeans($quote) as $key => $option) {
            $means = $option['means'];

            $icon = $this->getCcTypeImageSrc($means);

            // Get supported payment means including added ones.
            $cards = $this->method->getSupportedPaymentMeans();

            $options[] = [
                'key' => $key,
                'value' => $cards[$means],
                'label' => $option['label'],
                'icon' => $icon
            ];
        }

        return $options;
    }
}
