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

class ColumnList extends \Magento\Framework\View\Element\AbstractBlock
{

    protected function _toHtml()
    {
        $column = $this->getColumn();

        $html = '<select name="' . $this->getInputName() . '"';
        $html .= ' class="' . ($column['class'] ? $column['class'] : 'input-text' . '"');
        $html .= $column['style'] ? ' style="' . $column['style'] . '"' : '';
        $html .= '>';

        foreach ($this->getOptions() as $code => $name) {
            $html .= '<option value="' . $code . '"<% if (' . $this->getColumnName() . ' == "' . $code . '") { %> selected="selected"<% } %>>' . __($name) . '</option>';
        }

        $html .= '</select>';
        return $html;
    }
}
