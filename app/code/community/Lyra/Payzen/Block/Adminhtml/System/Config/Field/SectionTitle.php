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
 * Custom renderer for the section title field.
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Field_SectionTitle extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Get element HTML code.
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<div style="background-color: #D1DEDF; border: 1px solid #849BA3; height: 26px; padding-top: 5px;">';
        $html .= '<p style="font-size: 11px; margin: 0; padding-left: 20px;"><b>' . $element->getLabel() . '</b></p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Unset some non-related element parameters.
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<tr style="height: 13px;"><td colspan="4"></td></tr>';
        $html .= '<tr>';
        $html .= '<td colspan="4">';
        $html .= $this->_getElementHtml($element);

        if ($element->getComment()) {
            $html.= '<p class="note" style="margin-left: 20px; margin-bottom: 15px;"><span>'
                .$element->getComment().'</span></p>';
        }

        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }
}
