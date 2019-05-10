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
 * Custom renderer for the init button field.
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Field_InitButton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template to itself.
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setTemplate('payzen/field/init_button.phtml');

        return $this;
    }

    /**
     * Unset some non-related element parameters.
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents.
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $fieldConfig = $element->getFieldConfig();

        $this->addData(
            array(
                'button_label' => Mage::helper('payzen')->__((string) $fieldConfig->button_label),
                'button_url'   => $this->getUrl($fieldConfig->button_url),
                'html_id' => $element->getHtmlId()
            )
        );

        return $this->_toHtml();
    }
}
