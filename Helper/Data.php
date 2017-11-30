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
namespace Lyranetwork\Payzen\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const METHOD_STANDARD = 'payzen_standard';

    const METHOD_MULTI = 'payzen_multi';

    const METHOD_GIFT = 'payzen_gift';

    const METHOD_ONEY = 'payzen_oney';

    const METHOD_PAYPAL = 'payzen_paypal';

    const METHOD_SOFORT = 'payzen_sofort';

    const METHOD_POSTFINANCE = 'payzen_postfinance';

    /**
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     *
     * @var \Magento\Framework\App\MaintenanceMode
     */
    protected $maintenanceMode;

    /**
     *
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     *
     * @var \Magento\Config\Model\Config\Structure
     */
    protected $configStructure;

    /**
     *
     * @var \Lyranetwork\Payzen\Model\Logger\Payzen
     */
    protected $logger;

    /**
     *
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     *
     * @var \Zend\Http\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     *
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    /**
     *
     * @param \Lyranetwork\Payzen\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\MaintenanceMode $maintenanceMode
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param \Lyranetwork\Payzen\Model\Logger\Payzen
     * @param \Magento\Framework\App\State $appState
     * @param \Zend\Http\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Framework\Filesystem\Io\File $file
     */
    public function __construct(
        \Lyranetwork\Payzen\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\MaintenanceMode $maintenanceMode,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Lyranetwork\Payzen\Model\Logger\Payzen $logger,
        \Magento\Framework\App\State $appState,
        \Zend\Http\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Filesystem\Io\File $file
    ) {
        parent::__construct($context);

        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->maintenanceMode = $maintenanceMode;
        $this->resourceConfig = $resourceConfig;
        $this->filesystem = $filesystem;
        $this->configStructure = $configStructure;
        $this->logger = $logger;
        $this->appState = $appState;
        $this->remoteAddress = $remoteAddress;
        $this->file = $file;
    }

    /**
     * Shortcut method to get general PayZen module configuration.
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     * @return mixed
     */
    public function getCommonConfigData($field, $storeId = null)
    {
        if ($storeId === null && $this->isBackend()) {
            $storeId = $this->getCheckoutStoreId();
        }

        return $this->scopeConfig->getValue('payzen/general/' . $field, ScopeInterface::SCOPE_STORE, $storeId);
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

    /**
     * Return true if this is a backend session.
     *
     * @return bool
     */
    public function isBackend()
    {
        return $this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
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
        $sessionClass = \Magento\Checkout\Model\Session::class;
        if ($this->isBackend()) {
            $sessionClass = \Magento\Backend\Model\Session\Quote::class;
        }

        return $this->objectManager->get($sessionClass);
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
        $parentPath = 'payment/' . $parts[1] . '/' . $parts[2]; // we need the second level group

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
        // retrieve DB connection
        $connection = $this->resourceConfig->getConnection();

        $select = $connection->select()
            ->from($this->resourceConfig->getMainTable())
            ->where('path LIKE ?', 'payment/payzen\_multi\_%x/model');

        return $connection->fetchAll($select);
    }

    /**
     * Check if server has requirements to do WS operations.
     *
     * @throws \Lyranetwork\Payzen\Model\WsException
     */
    public function checkWsRequirements()
    {
        if (! extension_loaded('soap')) {
            throw new \Lyranetwork\Payzen\Model\WsException(
                'SOAP extension for PHP must be enabled on the server in order to use PayZen web services.'
            );
        }

        if (! extension_loaded('openssl')) {
            throw new \Lyranetwork\Payzen\Model\WsException(
                'OPENSSL extension for PHP must be enabled on the server in order to use PayZen web services.'
            );
        }
    }

    /**
     * Unserialize data using JSON or PHP unserialize function if error.
     *
     * @param string $string
     * @return mixed
     */
    public function unserialize($string)
    {
        $result = json_decode($string, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Magento 2.1.x : try PHP serialization
            $result = @unserialize($string);
        }

        return $result;
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

        $log = '';
        $log .= 'PayZen 2.1.3';
        $log .= ' - ' . $currentMethod;
        $log .= ' : ' . $message;

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
}
