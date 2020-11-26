<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend\Serialized\ArraySerialized;

class ConfigArraySerialized extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
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
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    protected function throwException($column, $position, $extraMsg = '')
    {
        $config = $this->getFieldConfig();

        // Translate field and column names.
        $field = __($config['label'])->render();
        $column = __($column)->render();
        $group = $this->dataHelper->getGroupTitle($config['path']);

        // Main message.
        $msg = __(
            'The field &laquo; %1 &raquo; is invalid: please check column &laquo; %2 &raquo; of the option %3 in section &laquo; %4 &raquo;.',
            $field,
            $column,
            $position,
            $group
        )->render();

        if ($extraMsg) {
            $msg .= "\n" . __($extraMsg)->render();
        }

        // Throw exception.
        throw new \Magento\Framework\Exception\LocalizedException(__($msg));
    }

    protected function checkMandatoryDecimal($value, $fieldLabel, $i)
    {
        if (empty($value)) {
            $this->throwException($fieldLabel, $i);
        }

        $this->checkDecimal($value, $fieldLabel, $i);
    }

    protected function checkDecimal($value, $fieldLabel, $i)
    {
        if (! empty($value) && ! preg_match('#^\d+$#', $value)) {
            $this->throwException($fieldLabel, $i);
        }
    }

    protected function checkAmount($amount, $fieldLabel, $i)
    {
        if (! empty($amount) && ! preg_match('#^\d+(\.\d+)?$#', $amount)) {
            $this->throwException($fieldLabel, $i);
        }
    }

    protected function checkRate($rate, $fieldLabel, $i)
    {
        if (! empty($rate) && (! is_numeric($rate) || ($rate < 0) || ($rate >= 100))) {
            $this->throwException($fieldLabel, $i);
        }
    }
}
