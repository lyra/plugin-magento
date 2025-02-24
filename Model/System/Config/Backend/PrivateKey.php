<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend;

use Lyranetwork\Payzen\Helper\Data;

class PrivateKey extends \Magento\Config\Model\Config\Backend\Encrypted
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Framework\Message\ManagerInterface $restHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
            $this->dataHelper = $dataHelper;
            $this->messageManager = $messageManager;

            parent::__construct($context, $registry, $config, $cacheTypeList, $encryptor, $resource, $resourceCollection, $data);
    }

    public function afterSave()
    {
        $value = $this->processValue($this->getValue());
        $config = $this->getFieldConfig();

        if (! empty($value)) {
            $prefix = $config['id'] == "rest_private_key_prod" ? "prodpassword_" : "testpassword_";
            if (substr($value, 0, strlen($prefix)) !== $prefix) {
                $field = __($config['label'])->render();
                $group = $this->dataHelper->getGroupTitle($config['path']);

                $msg = '[PayZen] ' . __('Invalid value for field &laquo; %1 &raquo; in section &laquo; %2 &raquo;.', $field, $group);
                $this->messageManager->addErrorMessage($msg);
            }
        }

        return parent::afterSave();
    }
}
