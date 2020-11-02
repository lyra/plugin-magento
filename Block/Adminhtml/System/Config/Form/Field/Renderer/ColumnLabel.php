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

class ColumnLabel extends \Magento\Framework\View\Element\AbstractBlock
{
    protected function _toHtml()
    {
        $column = $this->getColumn();

        $codeInputName = str_replace($this->getColumnName(), 'code', $this->getInputName());
        $html = '<input type="hidden" value="<%- code %>" name="' . $codeInputName . '">';

        $html .= '<div';
        $html .= ' class="' . ($column['class'] ? $column['class'] : 'input-text') . '"';
        $html .= ' style="display: inline;' . ($column['style'] ? ' ' . $column['style'] : '') . '">';

        $html .= '<%- ' . $this->getColumnName() . ' %><% if (typeof mark != "undefined" && mark) { %><span style="color: red;">*</span><% } %>';
        $html .= '</div>';

        return $html;
    }
}
