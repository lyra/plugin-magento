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
 * Custom renderer for the Choozeo payment options field.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Choozeo_PaymentOptions
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn(
            'label',
            array(
                'label' => Mage::helper('payzen')->__('Label'),
                'style' => 'width: 260px;',
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_Label
            )
        );
        $this->addColumn(
            'amount_min',
            array(
                'label' => Mage::helper('payzen')->__('Minimum amount'),
                'style' => 'width: 210px;'
            )
        );
        $this->addColumn(
            'amount_max',
            array(
                'label' => Mage::helper('payzen')->__('Maximum amount'),
                'style' => 'width: 210px;'
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
            'EPNF_3X' => 'Choozeo 3X CB',
            'EPNF_4X' => 'Choozeo 4X CB'
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
                'amount_min' => '',
                'amount_max' => ''
            );

            $savedOptions[uniqid('_' . $code . '_')] = $option;
        }

        $this->getElement()->setValue($savedOptions);
        return parent::getArrayRows();
    }
}
