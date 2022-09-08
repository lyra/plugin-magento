<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend\Other;

use Magento\Framework\App\Filesystem\DirectoryList;

class AddedPaymentMeans extends \Lyranetwork\Payzen\Model\System\Config\Backend\Serialized\ArraySerialized\ConfigArraySerialized
{
    /**
     * @var \Lyranetwork\Payzen\Model\Method\Other
     */
    protected $method;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Model\Method\Other $method
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
        \Lyranetwork\Payzen\Model\Method\Other $method,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->method = $method;

        parent::__construct($context, $registry, $config, $cacheTypeList,$dataHelper, $resource, $resourceCollection, $data);
    }

    /**
     * Save uploaded files before saving config value.
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (! is_array($value) || empty($value)) {
            $this->setValue([]);
            return parent::beforeSave();
        }

        $i = 0;
        $usedCards = [];

        // Get supported payment means.
        $supportedCards = \Lyranetwork\Payzen\Model\Api\Form\Api::getSupportedCardTypes();

        foreach ($value as $key => $card) {
            $i++;

            if (empty($card)) {
                continue;
            }

            // Trim value before set.
            $card['meanCode'] = trim($card['meanCode']);
            $value[$key]['meanCode'] = $card['meanCode'];

            $this->checkCode($card['meanCode'], $i);
            $this->checkName($card['meanName'], $i);

            if (in_array($card['meanCode'], $supportedCards) || in_array($card['meanCode'], $usedCards)) {
                unset($value[$key]);
            } else {
                $usedCards[] = $card['meanCode'];
            }
        }

        $this->setValue($value);

        return parent::beforeSave();
    }

    private function checkCode($value, $i)
    {
        if (empty($value) || ! preg_match('#^[A-Za-z0-9\-_]+$#', $value)) {
            $this->throwException('Code', $i);
        }
    }

    private function checkName($value, $i)
    {
        if (empty($value) || ! preg_match('#^[^<>]*$#', $value)) {
            $this->throwException('Label', $i);
        }
    }
}
