<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_EditableLabel extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $html = '<div';
        $html .= ' class="' . ($this->column['class'] ? $this->column['class'] : 'input-text') . '"';
        $html .= ' style="'. ($this->column['style'] ? ' ' . $this->column['style'] : '') . '"';
        $html .= '>';

        $html .= '<input class="input-text" type="text" value="#{label}" name="' . $this->inputName
        . '" style="width: 210px;">';

        $codeInputName = str_replace($this->columnName, 'code', $this->inputName);
        $html .= '<input type="text" value="#{code}" name="' . $codeInputName
            . '" style="width: 0px; display: none;">';

        $countInputName = str_replace($this->columnName, 'count', $this->inputName);
        $html .= '<input type="text" value="#{count}" name="' . $countInputName
            . '" style="width: 0px; display: none;">';

        $html .= '</div>';

        return $html;
    }
}
