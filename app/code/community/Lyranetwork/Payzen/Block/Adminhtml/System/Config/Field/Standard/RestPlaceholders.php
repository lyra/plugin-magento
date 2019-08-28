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
 * Custom renderer for the customer group options field.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Standard_RestPlaceholders
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    public function __construct()
    {
        $this->addColumn(
            'label',
            array(
                'label' => Mage::helper('payzen')->__('Label'),
                'style' => 'width: 210px;',
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_Label
            )
         );
        $this->addColumn(
            'placeholder',
            array(
                'label' => Mage::helper('payzen')->__('Placeholder'),
                'style' => 'width: 260px;'
            )
        );

        $this->_addAfter = false;

        parent::__construct();

        $this->setTemplate('payzen/field/array.phtml');
    }

    /**
     * Obtain existing data from form element.
     *
     * Each row will be instance of Varien_Object
     *
     * @return array
     */
    public function getArrayRows()
    {
        /**
         * @var array[string][string] $options
         */
        $options = array(
            'pan'     => Mage::helper('payzen')->__('Card Number'),
            'expiry'  => Mage::helper('payzen')->__('Expiration Date'),
            'cvv'     => Mage::helper('payzen')->__('CVV'),
        );

        $savedOptions = $this->getElement()->getValue();
        if (! is_array($savedOptions)) {
            $savedOptions = array();
        }

        foreach ($savedOptions as $id => $savedOption) {
            if (key_exists($savedOption['code'], $options)) {
                $savedOptions[$id]['label'] = $options[$savedOption['code']];
                unset($options[$savedOption['code']]);
            }
        }

        // Add not saved yet groups.
        foreach ($options as $code => $label) {
            $option = array(
                'code' => $code,
                'label' => $label,
                'placeholder' => ''
            );

            $savedOptions[$code] = $option;
        }

        $this->getElement()->setValue($savedOptions);
        return parent::getArrayRows();
    }
}
