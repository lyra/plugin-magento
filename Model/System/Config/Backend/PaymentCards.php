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

class PaymentCards extends \Magento\Framework\App\Config\Value
{
    protected $messages;

    /**
     * @var \Lyranetwork\Payzen\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper
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
        \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->checkoutHelper = $checkoutHelper;
        $this->dataHelper = $dataHelper;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function save()
    {
        $this->messages = [];

        if (! is_array($this->getValue()) || in_array('', $this->getValue())) {
            $this->setValue([]);
        }

        // Remove Oney cards from payment means list.
        $this->setValue(array_diff($this->getValue(), ['ONEY_3X_4X', 'ONEY_10x_12X', 'ONEY_PAYLATER']));

        if (strlen(implode(';', $this->getValue())) > 127) {
            $config = $this->getFieldConfig();

            $field = __($config['label'])->render();
            $group = $this->dataHelper->getGroupTitle($config['path']);

            $msg = '[PayZen] ' . __('Invalid value for field &laquo; %1 &raquo; in section &laquo; %2 &raquo;.', $field, $group)->render();
            $msg .= ' ' . __('Too many card types are selected.')->render();
            throw new \Magento\Framework\Exception\LocalizedException(__($msg));
        }

        return parent::save();
    }
}
