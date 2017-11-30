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
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\UrlInterface;

class ColumnUploadButton extends \Magento\Framework\View\Element\AbstractBlock
{

    /**
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param array $data
     */
    public function __construct(\Magento\Framework\View\Element\Context $context, array $data = [])
    {
        $this->url = $context->getUrlBuilder();

        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        $column = $this->getColumn();

        $html = '<div' . ($column['style'] ? ' style="' . $column['style'] . '"' : '') . '>';

        $html .= '<input type="file" name="' . $this->getInputName() . '" value="<%- ' . $this->getColumnName() . ' %>"';
        $html .= ' class="' . ($column['class'] ? $column['class'] : 'input-text') . '"';
        if ($column['size']) {
            $html .= ' size="' . $column['size'] . '"';
        }
        $html .= '/>';

        $src = $this->url->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'payzen/gift/<%- logo %>?' . time();
        $html .= '<img style="margin-left: 10px; vertical-align: middle; height: 18px;" alt="<%- code %>" src="' . $src . '" title="<%- name %>}" >';

        $html .= '</div>';

        return $html;
    }
}
