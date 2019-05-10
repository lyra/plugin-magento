<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyra_Payzen_Block_Adminhtml_System_Config_Field_Column_Label extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $html = '<div';
        $html .= ' class="' . ($this->column['class'] ? $this->column['class'] : 'input-text') . '"';
        $html .= ' style="background-color: #{color};' .
            ($this->column['style'] ? ' ' . $this->column['style'] : '') . '"';
        $html .= '>';

        $codeInputName = str_replace($this->columnName, 'code', $this->inputName);
        $html .= '<input type="text" value="#{code}" name="' . $codeInputName
            . '" style="width: 0px; visibility: hidden;">';

        $html .= '#{' . $this->columnName . '}<span style="color: red;">#{mark}</span>';
        $html .= '</div>';

        return $html;
    }
}
