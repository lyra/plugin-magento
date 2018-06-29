<?php
/**
 * PayZen V2-Payment Module version 1.9.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
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
