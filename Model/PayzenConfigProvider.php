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

class PayzenConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Lyranetwork\Payzen\Model\Method\Payzen
     */
    protected $method;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param string $methodCode
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        $methodCode
    ) {
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->dataHelper = $dataHelper;
        $this->method = $this->dataHelper->getMethodInstance($methodCode);

        $this->method->setStore($this->storeManager->getStore()->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'payment' => [
                $this->method->getCode() => [
                    'checkoutRedirectUrl' => $this->getCheckoutRedirectUrl(),
                    'moduleLogoUrl' => $this->getModuleLogoUrl(),
                    'availableCcTypes' => $this->getAvailableCcTypes(),
                    'entryMode' => $this->getEntryMode()
                ]
            ]
        ];
    }

    /**
     * Checkout redirect URL getter.
     *
     * @return string
     */
    protected function getCheckoutRedirectUrl()
    {
        $params = [
            '_secure' => true
        ];

        return $this->urlBuilder->getUrl('payzen/payment/redirect', $params);
    }

    protected function getModuleLogoUrl()
    {
        $fileName = $this->method->getConfigData('module_logo');

        return $this->getCcTypeImageSrc($fileName, false);
    }

    protected function getAvailableCcTypes()
    {
        if (! method_exists($this->method, 'getAvailableCcTypes') || ! $this->method->getAvailableCcTypes()) {
            return null;
        }

        $cards = [];
        foreach ($this->method->getAvailableCcTypes() as $value => $label) {
            $icon = $this->getCcTypeImageSrc($value);

            $cards[] = [
                'value' => $value,
                'label' => $label,
                'icon' => $icon
            ];
        }

        return $cards;
    }

    protected function getEntryMode()
    {
        if (! method_exists($this->method, 'getEntryMode')) {
            return null;
        }

        return $this->method->getEntryMode();
    }

    protected function getCcTypeImageSrc($card, $cc = true)
    {
        return $this->dataHelper->getCcTypeImageSrc($card, $cc);
    }

    protected function renderMaskedPan($maskedPan)
    {
        // Recover card brand if saved with masked pan and check if logo exists.
        if (strpos($maskedPan, '|') !== false) {
            $cardBrand = substr($maskedPan, 0, strpos($maskedPan, '|'));

            $logoSrc = $this->getCcTypeImageSrc($cardBrand);
            if ($logoSrc) {
                $logo = '<img src="' . $logoSrc . '"
                        alt="' . $cardBrand . '"
                        title="' . $cardBrand . '"
                        style="vertical-align: middle; margin: 0 10px 0 5px; max-height: 20px; display: unset;">';
            }

            return $logoSrc ? $logo . '<span style="vertical-align: middle;">' . substr($maskedPan, strpos($maskedPan, '|') + 1) .
                '</span>' : str_replace('|',' ', $maskedPan);
        }

        return $maskedPan;
    }
}
