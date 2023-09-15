<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Helper;

use Lyranetwork\Payzen\Model\Api\Form\Api as PayzenApi;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const METHOD_STANDARD = 'payzen_standard';
    const METHOD_MULTI = 'payzen_multi';
    const METHOD_CHOOZEO = 'payzen_choozeo';
    const METHOD_SEPA = 'payzen_sepa';
    const METHOD_GIFT = 'payzen_gift';
    const METHOD_ONEY = 'payzen_oney';
    const METHOD_PAYPAL = 'payzen_paypal';
    const METHOD_FULLCB = 'payzen_fullcb';
    const METHOD_FRANFINANCE = 'payzen_franfinance';
    const METHOD_OTHER = 'payzen_other';

    const MODE_FORM = 1;
    const MODE_LOCAL_TYPE = 2;
    const MODE_IFRAME = 3;
    const MODE_EMBEDDED = 4;
    const MODE_SMARTFORM = 5;
    const MODE_SMARTFORM_EXT_WITH_LOGOS = 6;
    const MODE_SMARTFORM_EXT_WITHOUT_LOGOS = 7;

    /**
     * @var array a global var to easily enable/disable features
     */
    public static $pluginFeatures = [
        'qualif' => false,
        'prodfaq' => true,
        'restrictmulti' => false,
        'shatwo' => true,
        'embedded' => true,
        'support' => true,

        'multi' => true,
        'gift' => true,
        'choozeo' => false,
        'oney' => true,
        'fullcb' => true,
        'sepa' => true,
        'paypal' => true,
        'franfinance' => true,
        'other' => true
    ];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\MaintenanceMode
     */
    protected $maintenanceMode;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Lyranetwork\Payzen\Model\Logger\Payzen
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Zend\Http\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    /**
     * @var \Magento\Framework\View\Asset\Repository $assetRepo
     */
    protected $assetRepo;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Config\Model\Config\Structure
     */
    protected $configStructure;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * \Magento\Backend\Model\Session\Quote
     */
    protected $backendSession;

    /**
     * @param \Lyranetwork\Payzen\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\MaintenanceMode $maintenanceMode
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Lyranetwork\Payzen\Model\Logger\Payzen
     * @param \Magento\Framework\App\State $appState
     * @param \Zend\Http\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Backend\Model\Session\Quote $backendSession
     */
    public function __construct(
        \Lyranetwork\Payzen\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\MaintenanceMode $maintenanceMode,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Lyranetwork\Payzen\Model\Logger\Payzen $logger,
        \Magento\Framework\App\State $appState,
        \Zend\Http\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Backend\Model\Session\Quote $backendSession
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->maintenanceMode = $maintenanceMode;
        $this->resourceConfig = $resourceConfig;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->appState = $appState;
        $this->remoteAddress = $remoteAddress;
        $this->file = $file;
        $this->assetRepo = $assetRepo;
        $this->paymentHelper = $paymentHelper;
        $this->productMetadata = $productMetadata;
        $this->configStructure = $configStructure;
        $this->checkoutSession = $checkoutSession;
        $this->backendSession = $backendSession;
    }

    /**
     * Shortcut method to get general module configuration.
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     * @return mixed
     */
    public function getCommonConfigData($field, $storeId = null)
    {
        if ($storeId === null && $this->isBackend()) {
            if ($this->_request->getParam('section', null) === 'payment') {
                $configWebsiteId = $this->_request->getParam('website', null);
                $configStoreId = $this->_request->getParam('store', null);

                if ($configWebsiteId !== null) {
                    $scope = ScopeInterface::SCOPE_WEBSITE;
                    $storeId = $configWebsiteId;
                } elseif ($configStoreId !== null) {
                    $scope = ScopeInterface::SCOPE_STORE;
                    $storeId = $configWebsiteId;
                } else {
                    $scope = 'default';
                }
            } else {
                $scope = ScopeInterface::SCOPE_STORE;
                $storeId = $this->getCheckoutStoreId();
            }
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        $value = $this->scopeConfig->getValue('payzen/general/' . $field, $scope, $storeId);
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Return user's IP Address.
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->remoteAddress->getIpAddress();
    }

    /**
     * Get the complete payment return URL.
     *
     * @param int $storeId the ID of the store
     * @return string
     */
    public function getReturnUrl($storeId = null)
    {
        $params = [];
        $params['_nosid'] = true;
        $params['_secure'] = $this->getStore($storeId)->isCurrentlySecure();

        if ($storeId) {
            $params['_scope'] = $storeId;
        }

        return $this->_getUrl('payzen/payment/response', $params);
    }

    public function getRestReturnUrl()
    {
        return $this->_getUrl('payzen/payment_rest/response', ['_secure' => true]);
    }

    /**
     * Return true if this is a backend session.
     *
     * @return bool
     */
    public function isBackend()
    {
        return $this->appState->getAreaCode() === \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
    }

    /**
     * Return true if SSL is enabled for current store.
     *
     * @return bool
     */
    public function isCurrentlySecure()
    {
        return $this->storeManager->getStore()->isCurrentlySecure();
    }

    /**
     * Return checkout session.
     *
     * @return Magento\Backend\Model\Session|Magento\Backend\Model\Session\Quote
     */
    public function getCheckout()
    {
        return $this->isBackend() ? $this->backendSession : $this->checkoutSession;
    }

    /**
     * Return current checkout store.
     *
     * @return int
     */
    public function getCheckoutStoreId()
    {
        $session = $this->getCheckout();
        return $session->getStoreId();
    }

    /**
     * Return store obeject by ID.
     *
     * @param int $storeId
     * @return \Magento\Store\Model\Store
     */
    public function getStore($storeId)
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     * Return current checkout quote.
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getCheckoutQuote()
    {
        $session = $this->getCheckout();
        return $session->getQuote();
    }

    /**
     * Return true if Magento shop is in maintenance mode.
     *
     * @return bool
     */
    public function isMaintenanceMode()
    {
        return $this->maintenanceMode->isOn($this->getIpAddress());
    }

    /**
     * Return true file exists.
     *
     * @param string $file
     * @param bool $onlyFile
     * @return bool
     */
    public function fileExists($file, $onlyFile = true)
    {
        return $this->file->fileExists($file, $onlyFile);
    }

    /**
     * Check if image file is uploaded to media directory.
     *
     * @param string $fileName
     * @return string
     */
    public function isUploadFileImageExists($fileName)
    {
        $filePath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('payzen/images/' . $fileName);
        return $this->fileExists($filePath);
    }

    /**
     * Check if image file is published to pub/static directory.
     *
     * @param string $fileName
     * @return string
     */
    public function isPublishFileImageExists($fileName)
    {
        $filePath = $this->filesystem->getDirectoryRead(DirectoryList::STATIC_VIEW)->getAbsolutePath($fileName);
        return $this->fileExists($filePath);
    }

    /**
     * Returns a configuration parameter from XML files.
     *
     * @param string $path
     * @return string
     */
    public function getGroupTitle($path)
    {
        // $path is as payment_[lang]/payzen/payzen_[group]/payzen_[sub_group]
        $parts = explode('/', $path);
        $parentPath = 'payment/' . $parts[1] . '/' . $parts[2]; // We need the second level group.

        return __($this->configStructure->getElement($parentPath)->getLabel())->render();
    }

    /**
     * Add a model config parameter for each of given $options (multi payment options).
     *
     * @param array[string][mixed] $options
     */
    public function updateMultiPaymentModelConfig($options)
    {
        foreach ($options as $option) {
            $this->resourceConfig->saveConfig(
                'payment/payzen_multi_' . $option['count'] . 'x/model',
                \Lyranetwork\Payzen\Model\Method\Multix::class,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }
    }

    /**
     * Get multi payment method models.
     *
     * @return array[int] $options
     */
    public function getMultiPaymentModelConfig()
    {
        // Retrieve DB connection.
        $connection = $this->resourceConfig->getConnection();

        $select = $connection->select()
            ->from($this->resourceConfig->getMainTable())
            ->where('path LIKE ?', 'payment/payzen\_multi\_%x/model');

        return $connection->fetchAll($select);
    }

    /**
     * Add a model config parameter for each of given $options (other payment options).
     *
     * @param array[string][mixed] $options
     */
    public function updateOtherPaymentModelConfig($options)
    {
        foreach ($options as $option) {
            $this->resourceConfig->saveConfig(
                'payment/payzen_other_' . $option['means'] . '/model',
                \Lyranetwork\Payzen\Model\Method\Other::class,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }
    }

    /**
     * Get other payment method models.
     *
     * @return array[int] $options
     */
    public function getOtherPaymentModelConfig()
    {
        // Retrieve DB connection.
        $connection = $this->resourceConfig->getConnection();

        $select = $connection->select()
            ->from($this->resourceConfig->getMainTable())
            ->where('path LIKE ?', 'payment/payzen\_other\_%/model');

        return $connection->fetchAll($select);
    }

    /**
     * Unserialize data using JSON or PHP unserialize function if error.
     *
     * @param string $string
     * @return mixed
     */
    public function unserialize($string)
    {
        if ($string === null) {
            return [];
        }

        $result = json_decode($string, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Magento 2.1.x: try PHP serialization.
            $result = @unserialize($string);
        }

        return $result;
    }

    /**
     * Break a string into an array.
     *
     * @param string $separator
     * @param string $string
     * @return array
     */
    public function explode($separator, $string)
    {
        if ($string === null) {
            return [];
        }

        return explode($separator, $string);
    }

    /**
     * Log function.
     *
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = \Psr\Log\LogLevel::INFO)
    {
        if (! $this->getCommonConfigData('enable_logs')) {
            return;
        }

        $currentMethod = $this->getCallerMethod();
        $version = $this->getCommonConfigData('plugin_version');
        $ipAddress = "[IP = {$this->getIpAddress()}]";

        $log = '';
        $log .= 'payzen ' . $version;
        $log .= ' - ' . $currentMethod;
        $log .= ' : ' . $message;
        $log .= ' ' . $ipAddress;

        $this->logger->log($level, $log);
    }

    /**
     * Find the name of the method that called the log method.
     *
     * @return string|null
     */
    private function getCallerMethod()
    {
        $traces = debug_backtrace();

        if (isset($traces[2])) {
            return $traces[2]['class'] . '::' . $traces[2]['function'];
        }

        return null;
    }

    /**
     * Return logged in customer model data.
     * @param \Magento\Customer\Model\Session
     *
     * @return int
     */
    public function getCurrentCustomer($customerSession)
    {
        // Customer not logged in.
        if (! $customerSession->isLoggedIn()) {
            return false;
        }

        // Customer has not gateway identifier.
        $customer = $customerSession->getCustomer();
        if (! $customer || ! $customer->getId()) {
            return false;
        }

        return $customer->getDataModel();
    }

    /**
     * Return card logo source path from upload directory or the online logo.
     * @param string $card
     *
     * @return string|boolean
     */
    public function getCcTypeImageSrc($card)
    {
        $name = strtolower($card) . '.png';

        if ($this->isUploadFileImageExists('cc/' . $card)) {
            return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .
                'payzen/images/cc/' . $name;
        } else {
            return $this->getCommonConfigData('logo_url') . $name;
        }
    }

    /**
     * Return submodule logo source path if exists, return false elsewhere.
     * @param string $card
     *
     * @return string|boolean
     */
    public function getLogoImageSrc($name)
    {
        if ($this->isUploadFileImageExists($name)) {
            return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .
                'payzen/images/' . $name;
        } else {
            // Default logo from the installed plugin.
            $asset = $this->assetRepo->createAsset('Lyranetwork_Payzen::images/' . $name);

            if ($this->isPublishFileImageExists($asset->getRelativeSourceFilePath())) {
                return $this->getViewFileUrl('Lyranetwork_Payzen::images/' . $name);
            }
        }

        return false;
    }

    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e);
            return $this->_getUrl('', [
                '_direct' => 'core/index/notFound'
            ]);
        }
    }

    public function getMethodInstance($methodCode)
    {
        return $this->paymentHelper->getMethodInstance($methodCode);
    }

    public function isOneClickActive()
    {
        $standardMethod = $this->getMethodInstance(\Lyranetwork\Payzen\Helper\Data::METHOD_STANDARD);
        $sepaMethod = $this->getMethodInstance(\Lyranetwork\Payzen\Helper\Data::METHOD_SEPA);

        return $standardMethod->isOneClickActive() || $sepaMethod->isOneClickActive();
    }

    private function convertCardDataEntryMode($code) {
        switch ($code) {
            case '1':
                return 'REDIRECT';

            case '2':
                return 'MERCHANT';

            case '3':
                return 'IFRAME';

            case '4':
                return'REST';

            case '5':
                return 'SMARTFROM';

            case '6':
                return 'SMARTFORM_EXT_WITH_LOGOS';

            case '7':
                return 'MODE_SMARTFORM_EXT_WITHOUT_LOGOS';

            default:
                return 'REDIRECT';
        }
    }

    public function getCardDataEntryMode($storeId = null)
    {
        if (! $this->isBackend()) {
            // Only backend is managed by this function.
            throw \BadMethodCallException('Call this function only from backend code.');
        }

        if ($storeId === null) {
            if ($this->_request->getParam('section', null) === 'payment') {
                $configWebsiteId = $this->_request->getParam('website', null);
                $configStoreId = $this->_request->getParam('store', null);

                if ($configWebsiteId !== null) {
                    $scope = ScopeInterface::SCOPE_WEBSITE;
                    $storeId = $configWebsiteId;
                } elseif ($configStoreId !== null) {
                    $scope = ScopeInterface::SCOPE_STORE;
                    $storeId = $configWebsiteId;
                } else {
                    $scope = 'default';
                }
            } else {
                $scope = ScopeInterface::SCOPE_STORE;
                $storeId = $this->getCheckoutStoreId();
            }
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        $value = $this->scopeConfig->getValue('payment/payzen_standard/card_info_mode', $scope, $storeId);
        return $this->convertCardDataEntryMode($value);
    }

    public function getContribParam() {
        $cmsParam = $this->getCommonConfigData('cms_identifier') . '_'
            . $this->getCommonConfigData('plugin_version');

        // Will return the Magento version.
        $cmsVersion = $this->productMetadata->getVersion();

        return $cmsParam . '/' . $cmsVersion . '/' . PayzenApi::shortPhpVersion();
    }
}
