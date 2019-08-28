<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Custom renderer for the multi select field.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_Multiselect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $html = '<select multiple="multiple" name="'. $this->inputName . '[]" currentvalue="#{' . $this->columnName . '}"';
        $html .= ' class="' . ($this->column['class'] ? $this->column['class'] : 'input-text')
            . ' select multiselect payzen_multiselect_' . $this->columnName . '"';
        $html .= $this->column['style'] ? ' style="' . $this->column['style'] . '"' : '';
        $html .= '>';

        foreach ($this->getData('options') as $code => $name) {
            $html .= '<option value="' . $code . '">' . addslashes($name) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }
}
