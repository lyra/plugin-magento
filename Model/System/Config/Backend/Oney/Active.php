<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Lyranetwork\Payzen\Model\System\Config\Backend\Oney;

class Active extends \Magento\Framework\App\Config\Value
{
    protected $messages;

    /**
     * @var \Lyranetwork\Payzen\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper
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
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->checkoutHelper = $checkoutHelper;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function save()
    {
        $this->messages = [];

        if ($this->getValue() /* Submodule enabled. */) {
            try {
                // Check Oney requirements.
                $this->checkoutHelper->checkOneyRequirements($this->getScope(), $this->getScopeId());
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->setValue(0);

                $this->messages[] = $e->getMessage();
            }

            $_SESSION['payzen_oney_enabled'] = 'True';
        }

        return parent::save();
    }

    public function afterCommitCallback()
    {
        if (! empty($this->messages)) {
            $this->messages[] = __('Payment in 3 or 4 times Oney cannot be used.');

            throw new \Magento\Framework\Exception\LocalizedException(__(implode("\n", $this->messages)));
        }

        return parent::afterCommitCallback();
    }
}
