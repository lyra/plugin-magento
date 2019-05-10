<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyra_Payzen_Block_Adminhtml_System_Config_Field_Column_List extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $html = '<select name="'. $this->inputName . '" currentvalue="#{' . $this->columnName . '}"';
        $html .= ' class="' . ($this->column['class'] ? $this->column['class'] : 'input-text')
            . ' payzen_list_' . $this->columnName . '"';
        $html .= $this->column['style'] ? ' style="' . $this->column['style'] . '"' : '';
        $html .= '>';

        foreach ($this->getData('options') as $code => $name) {
            $html .= '<option value="' . $code . '">' . Mage::helper('payzen')->__($name) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }
}
