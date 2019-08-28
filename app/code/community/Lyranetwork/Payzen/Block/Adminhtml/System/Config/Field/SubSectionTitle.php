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
 * Custom renderer for gateway sub-section.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_SubSectionTitle extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Get element HTML code.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<div style="background: none; padding: 0 0 0; border: 0;" id="' . $element->getHtmlId() . '">';
        $html .= '<a style="background: none; padding: 0; display: inline-block; border-bottom: 1px dotted #f67610;
                            color: #f67610!important; line-height: 1.1; white-space: nowrap; font-weight: bold;
                            font-size: 1em;" href="javascript: void(0);" onclick="payzenSubSectionToggle(); return false;">' . $element->getLabel() . '</a>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Unset some non-related element parameters.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {

        $html = '<td colspan="4" class="label">';
        $html .= $this->_getElementHtml($element);

        if ($element->getComment()) {
            $html.= '<p class="note" style="margin-left: 20px; margin-bottom: 15px;"><span>'
                .$element->getComment().'</span></p>';
        }

        $html .= $this->_getScript($element);

        $html .= '</td>';

        return $this->_decorateRowHtml($element, $html);
    }

    private function _getScript(Varien_Data_Form_Element_Abstract $element)
    {
        $fieldConfig = $element->getFieldConfig();
        $fields = isset($fieldConfig->included_fields) ? explode(',', $fieldConfig->included_fields) : array();

        $prefix = 'row_' . str_replace($fieldConfig->getName(), '', $element->getHtmlId());

        $script = '';
        $script .= '<script type="text/javascript">
                    // <![CDATA[
                       function payzenSubSectionToggle() {';

        foreach ($fields as $field) {
            $script .= '    $("' . $prefix . $field . '").toggle();' . "\n";
        }

        $script .= '   }
                    //]]>
                    </script>';

        return $script;
    }
}
