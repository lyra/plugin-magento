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
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     *
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     *
     * @var \Lyranetwork\Payzen\Model\Method\Payzen
     */
    protected $method;

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
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        $methodCode
    ) {
        $this->storeManager = $storeManager;
        $this->assetRepo = $assetRepo;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
        $this->method = $paymentHelper->getMethodInstance($methodCode);
        $this->dataHelper = $dataHelper;

        $this->method->setStore($this->storeManager->getStore()->getId());
    }

    /**
     *
     * {@inheritdoc}
     *
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

        if ($this->dataHelper->isUploadFileImageExists($fileName)) {
            return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .
                 'payzen/images/' . $fileName;
        } else {
            return $this->getViewFileUrl('Lyranetwork_Payzen::images/' . $fileName);
        }
    }

    /**
     * Retrieve URL of a view file.
     *
     * @param string $fileId
     * @param array $params
     * @return string[]
     */
    protected function getViewFileUrl($fileId, array $params = [])
    {
        try {
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e);
            return $this->urlBuilder->getUrl('', [
                '_direct' => 'core/index/notFound'
            ]);
        }
    }

    protected function getAvailableCcTypes()
    {
        if (! method_exists($this->method, 'getAvailableCcTypes') || ! $this->method->getAvailableCcTypes()) {
            return null;
        }

        $cards = [];
        foreach ($this->method->getAvailableCcTypes() as $value => $label) {
            $card = 'cc/' . strtolower($value) . '.png';

            $icon = false;
            if ($this->dataHelper->isUploadFileImageExists($card)) {
                $icon = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .
                    'payzen/images/' . $card;
            } else {
                $asset = $this->assetRepo->createAsset('Lyranetwork_Payzen::images/' . $card);

                if ($this->dataHelper->isPublishFileImageExists($asset->getRelativeSourceFilePath())) {
                    $icon = $this->getViewFileUrl('Lyranetwork_Payzen::images/' . $card);
                }
            }

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
}
