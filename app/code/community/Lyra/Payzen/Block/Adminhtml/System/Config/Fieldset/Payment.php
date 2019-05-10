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
 * Fieldset renderer for PayPal solution
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Fieldset_Payment
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Render fieldset html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        // Get configured features.
        $features = Lyra_Payzen_Helper_Data::$pluginFeatures;

        $data = $element->getOriginalData();


        if (isset($data['feature']) && key_exists($data['feature'], $features) && ! $features[$data['feature']]) {
            return '';
        }

        if (isset($data['feature']) && ($data['feature'] === 'multi')) {
            $comment = '';
            if ($features['restrictmulti']) {
                $comment = '<p style="background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; margin: 0 0 20px; padding: 10px;">';
                $comment .= $element->getComment();
                $comment .= '</p>';
            }

            $element->setComment($comment);
        }

        return parent::render($element);
    }
}
