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
 * Custom renderer for the context mode field.
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Field_KeyTest extends Lyra_Payzen_Block_Adminhtml_System_Config_Field_Sensitive
{
    /**
     * Render field HTML.
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        // Get configured features.
        $features = Lyra_Payzen_Helper_Data::$pluginFeatures;

        if ($features['qualif']) {
            return '';
        }

        return parent::render($element);
    }
}
