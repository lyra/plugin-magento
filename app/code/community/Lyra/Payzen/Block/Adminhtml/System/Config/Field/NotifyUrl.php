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
 * Custom renderer for the notify URL field.
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Field_NotifyUrl extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Unset some non-related element parameters.
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        $store = Mage::app()->getAnyStoreView(); // Either default or any other store view.
        $ipnUrl = Mage::helper('payzen')->prepareUrl($element->getValue(), $store->getId());
        $element->setValue($ipnUrl);

        $warnImg = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'payzen/warn.png';
        $comment = '<img src="' . $warnImg . '" style="vertical-align: top; padding-right: 5px;"/>';
        $comment .= '<span style="color: red; display: inline-block; font-weight:bold;">'
            . $element->getComment() . '</span>';
        $element->setComment($comment);

        return str_replace('class="note"', '', parent::render($element));
    }
}
