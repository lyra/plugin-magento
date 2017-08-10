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
namespace Lyranetwork\Payzen\Model;

class PayzenConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

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
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
                'payment' => [
                        $this->method->getCode() => [
                                'checkoutRedirectUrl' => $this->getCheckoutRedirectUrl(),
                                'moduleLogoUrl' => $this->getModuleLogoUrl()
                        ]
                ]
        ];
    }

    protected function getModuleLogoUrl()
    {
        $fileName = $this->method->getConfigData('module_logo');

        if ($this->dataHelper->isUploadFileImageExists($fileName)) {
            return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                . 'payzen/images/' . $fileName;
        } else {
            return $this->getViewFileUrl('Lyranetwork_Payzen::images/' . $fileName);
        }
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string[]
     */
    private function getViewFileUrl($fileId, array $params = [])
    {
        try {
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e);
            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }

    /**
     * Checkout redirect URL getter
     *
     * @return string
     */
    protected function getCheckoutRedirectUrl()
    {
        return $this->urlBuilder->getUrl('payzen/payment/redirect', ['_secure' => true]);
    }
}
