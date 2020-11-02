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

class ColumnList extends \Magento\Framework\View\Element\AbstractBlock
{
    protected function _toHtml()
    {
        $column = $this->getColumn();

        $html = '<select name="' . $this->getInputName() . '"';
        $html .= ' class="' . ($column['class'] ? $column['class'] : 'input-text') . '"';
        $html .= $column['style'] ? ' style="' . $column['style'] . '"' : '';
        $html .= '>';

        foreach ($this->getOptions() as $code => $name) {
            $html .= '<option value="' . $code . '"<% if (typeof ' . $this->getColumnName() . ' != "undefined" && '
                . $this->getColumnName() . ' == "' . $code . '") { %> selected="selected"<% } %>>' . __($name) . '</option>';
        }

        $html .= '</select>';
        return $html;
    }
}
