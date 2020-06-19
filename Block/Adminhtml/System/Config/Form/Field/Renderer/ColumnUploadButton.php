<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\UrlInterface;

class ColumnUploadButton extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     *
     * @var\Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->storeManager = $storeManager;
    }

    protected function _toHtml()
    {
        // Get a frontend store object to compute upload URL.
        $store = $this->storeManager->getDefaultStoreView();

        if (! $store) { // If no default store, retrieve any other.
            foreach ($this->storeManager->getStores() as $aStore) {
                $store = $aStore;
                break;
            }
        }

        $column = $this->getColumn();

        $html = '<div' . ($column['style'] ? ' style="' . $column['style'] . '"' : '') . '>';

        $html .= '<input type="file" name="' . $this->getInputName() . '" value="<%- ' . $this->getColumnName() . ' %>"';
        $html .= ' class="' . ($column['class'] ? $column['class'] : 'input-text') . '"';
        if ($column['size']) {
            $html .= ' size="' . $column['size'] . '"';
        }

        $html .= '/>';

        $src = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'payzen/images/cc/<%- logo %>?' . time();
        $html .= '<img style="margin-left: 10px; vertical-align: middle; height: 18px;" alt="<%- code %>" src="' . $src . '" title="<%- name %>" >';

        $html .= '</div>';

        return $html;
    }
}
