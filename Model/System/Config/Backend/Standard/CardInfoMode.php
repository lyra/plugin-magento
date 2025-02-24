<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend\Standard;

use Lyranetwork\Payzen\Helper\Data;

class CardInfoMode extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Lyranetwork\Payzen\Helper\Rest
     */
    protected $restHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Rest $restHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Rest $restHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
        ) {
            $this->dataHelper = $dataHelper;
            $this->restHelper = $restHelper;
            $this->messageManager = $messageManager;

            parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $value = $this->getValue();
        $restModes = [
            Data::MODE_EMBEDDED,
            Data::MODE_SMARTFORM,
            Data::MODE_SMARTFORM_EXT_WITH_LOGOS,
            Data::MODE_SMARTFORM_EXT_WITHOUT_LOGOS
        ];

        if (in_array($value, $restModes)) {
            // Get data of general config group.
            $generalData = $this->getGroups('payzen')['groups']['payzen_general']['groups'];

            $ctxMode = $generalData['payzen_platform_access']['fields']['ctx_mode']['value'];
            $key = ($ctxMode === 'PRODUCTION') ? 'prod' : 'test';

            $restKeys = $generalData['payzen_rest_api_keys']['fields'];

            $privateKey = isset($restKeys['rest_private_key_' . $key]['value']) ? $restKeys['rest_private_key_' . $key]['value'] : '';
            $publicKey = isset($restKeys['rest_public_key_' . $key]['value']) ? $restKeys['rest_public_key_' . $key]['value'] : '';
            $returnKey = isset($restKeys['rest_return_key_' . $key]['value']) ? $restKeys['rest_return_key_' . $key]['value'] : '';

            if (! $privateKey) {
                // Client has not configured private key in module backend.
                $field = ($ctxMode === 'PRODUCTION') ? 'Production password' : 'Test password';
                $this->throwException($field);
            }

            if (! $publicKey) {
                // Client has not configured public key in module backend.
                $field = ($ctxMode === 'PRODUCTION') ? 'Public production key' : 'Public test key';
                $this->throwException($field);
            }

            if (! $returnKey) {
                // Client has not configured HMAC-SHA-256 key in module backend.
                $field = ($ctxMode === 'PRODUCTION') ? 'HMAC-SHA-256 production key' : 'HMAC-SHA-256 test key';
                $this->throwException($field);
            }
        }

        return parent::beforeSave();
    }

    protected function throwException($field)
    {
        $section = __('REST API KEYS');
        $this->dataHelper->log("Cannot enable embedded / Smartform mode: {$field} is not configured.");
        $msg = '[PayZen] ' . __('The selected &laquo; Payment data entry mode &raquo; cannot be enabled: field &laquo; %1 &raquo; in section &laquo; %2 &raquo; is not configured.', __($field)->render(), $section->render())->render();

        $this->messageManager->addErrorMessage($msg);
        $this->setValue($this->getOldValue());
    }
}
