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
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field;

/**
 * Custom renderer for the PayZen notify URL.
 */
class NotifyUrl extends Label
{

    /**
     *
     * @param \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Context $context
     * @param array $data
     */
    public function __construct(
        \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Unset some non-related element parameters.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $store = $this->_storeManager->getDefaultStoreView();

        if (! $store) { // if no default store, retrieve any other
            foreach ($this->_storeManager->getStores() as $aStore) {
                $store = $aStore;
                break;
            }
        }

        $params = [
            '_secure' => $store->isCurrentlySecure(),
            '_nosid' => true
        ];

        $notifyUrl = $this->_urlBuilder->setScope($store->getId())
            ->getUrl($element->getValue(), $params);
        $element->setValue($notifyUrl);

        return parent::render($element);
    }
}
