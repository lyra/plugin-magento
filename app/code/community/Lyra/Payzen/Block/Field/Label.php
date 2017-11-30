<?php
/**
 * PayZen V2-Payment Module version 1.8.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */

/**
 * Custom renderer for the PayZen label.
 */
class Lyra_Payzen_Block_Field_Label extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Unset some non-related element parameters.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        $fieldConfig = $element->getFieldConfig();
        if (isset($fieldConfig->is_country) && (bool)$fieldConfig->is_country) {
            $name = Mage::app()->getLocale()->getCountryTranslation($element->getValue());
            if (! empty($name)) {
                $element->setValue($name);
            }
        }

        return parent::render($element);
    }
}
