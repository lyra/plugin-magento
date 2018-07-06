<?php
/**
 * PayZen V2-Payment Module version 1.9.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */

/**
 * Custom renderer for the PayZen customer group options field.
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Field_Choozeo_PaymentOptions
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn(
            'label',
            array(
                'label' => Mage::helper('payzen')->__('Label'),
                'style' => 'width: 260px;',
                'renderer' => new Lyra_Payzen_Block_Adminhtml_System_Config_Field_Column_Label
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
        /** @var array[string][string] $options */
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

        // add not saved yet groups
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
