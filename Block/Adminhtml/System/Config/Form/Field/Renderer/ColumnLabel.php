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

class ColumnLabel extends \Magento\Framework\View\Element\AbstractBlock
{

    protected function _toHtml()
    {
        $column = $this->getColumn();

        $codeInputName = str_replace($this->getColumnName(), 'code', $this->getInputName());
        $html = '<input type="text" value="<%- code %>" name="' . $codeInputName . '" style="width: 0px; visibility: hidden;">';

        $html .= '<div';
        $html .= ' class="' . ($column['class'] ? $column['class'] : 'input-text') . '"';
        $html .= ' style="display: inline;' . ($column['style'] ? ' ' . $column['style'] : '') . '">';

        $html .= '<%- ' . $this->getColumnName() . ' %><% if (typeof mark != "undefined" && mark) { %><span style="color: red;">*</span><% } %>';
        $html .= '</div>';

        return $html;
    }
}
