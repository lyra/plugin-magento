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
 * Custom renderer for the contact us element.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_ContactUs extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Render field HTML.
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $comment = Lyranetwork_Payzen_Model_Api_Api::formatSupportEmails('support@payzen.eu');
        $element->setComment($comment);

        return parent::render($element);
    }
}
