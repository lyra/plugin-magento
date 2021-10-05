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

use Lyranetwork\Payzen\Model\System\Config\Backend\Serialized\ArraySerialized\ConfigArraySerialized;

class OtherPaymentMeans extends ConfigArraySerialized
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

    public function beforeSave()
    {
        $values = $this->getValue();
        $usedCards = [];

        if (! is_array($values) || empty($values)) {
            $this->setValue([]);
        } else {
            $i = 0;
            $options = [];

            // Get supported payment means including added ones.
            $supportedCards = $this->method->getSupportedPaymentMeans();

            foreach ($values as $key => $value) {
                $i++;

                if (empty($value)) {
                    continue;
                }

                if (in_array($value['means'], $usedCards)) {
                    // Do not save several options with the same means of payment.
                    $this->throwException('Payment means', $i, 'You cannot enable several options with the same means of payment.');
                } else {
                    $usedCards[] = $value['means'];
                }

                if (empty($value['label'])) {
                    $value['label'] = sprintf(__('Payment with %s'), $supportedCards[$value['means']]);
                    $values[$key] = $value;
                }

                if (isset($value['minimum'])) {
                    $this->checkAmount($value['minimum'], 'Min. amount', $i);
                }

                if (isset($value['maximum'])) {
                    $this->checkAmount($value['maximum'], 'Max. amount', $i);
                }

                if (isset($value['capture_delay'])) {
                    $this->checkDecimal($value['capture_delay'], 'Capture delay', $i);
                }

                $options[] = $value;
            }

            $this->setValue($values);
            $this->dataHelper->updateOtherPaymentModelConfig($options);
        }

        return parent::beforeSave();
    }
}
