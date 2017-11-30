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
namespace Lyranetwork\Payzen\Model;

class MultiConfigProvider extends \Lyranetwork\Payzen\Model\PayzenConfigProvider
{

    /**
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param string $methodCode
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Lyranetwork\Payzen\Helper\Data $dataHelper
    ) {
        parent::__construct(
            $storeManager,
            $assetRepo,
            $urlBuilder,
            $logger,
            $paymentHelper,
            $dataHelper,
            \Lyranetwork\Payzen\Helper\Data::METHOD_MULTI
        );
    }

    /**
     *
     * {@inheritdoc}
     *
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
            $options[] = [
                'key' => $key,
                'label' => $option['label']
            ];
        }

        return $options;
    }
}
