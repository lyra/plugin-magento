<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field;

/**
 * Custom renderer for the notify URL.
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

        if (! $store) { // If no default store, retrieve any other.
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

        $warnImg = $this->getViewFileUrl('Lyranetwork_Payzen::images/warn.png');
        $comment = '<img src="' . $warnImg . '" style="vertical-align: top; padding-right: 5px;"/>';
        $comment .= '<span style="color: red; font-weight: bold; display: inline-block; width: 88%;">'
            . $element->getComment() . '</span>';
        $element->setComment($comment);

        return parent::render($element);
    }
}
