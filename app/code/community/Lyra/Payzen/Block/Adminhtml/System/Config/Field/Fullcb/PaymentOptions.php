<?php
/**
  * PayZen V2-Payment Module version 1.9.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Custom renderer for the PayZen customer group options field.
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Field_Fullcb_PaymentOptions
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn(
            'label',
            array(
                'label' => Mage::helper('payzen')->__('Label'),
                'style' => 'width: 220px;',
                'renderer' => new Lyra_Payzen_Block_Adminhtml_System_Config_Field_Column_EditableLabel
            )
        );

        $this->addColumn(
            'amount_min',
            array(
                'label' => Mage::helper('payzen')->__('Min. amount'),
                'style' => 'width: 120px;'
            )
        );

        $this->addColumn(
            'amount_max',
            array(
                'label' => Mage::helper('payzen')->__('Max. amount'),
                'style' => 'width: 120px;'
            )
        );

        $this->addColumn(
            'rate',
            array(
                'label' => Mage::helper('payzen')->__('Rate'),
                'style' => 'width: 100px;',
            )
        );

        $this->addColumn(
            'cap',
            array(
                'label' => Mage::helper('payzen')->__('Cap'),
                'style' => 'width: 100px;',
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
         * @var array[string][array] $defaultOptions
         */
        $defaultOptions = array(
            'FULLCB3X' =>  array(
                'label' => Mage::helper('payzen')->__('Payment in %s times', '3'),
                'count' => '3',
                'rate'  => '1.4',
                'cap'   => '9'
            ),
            'FULLCB4X' => array(
                'label' => Mage::helper('payzen')->__('Payment in %s times', '4'),
                'count' => '4',
                'rate'  => '2.1',
                'cap'   => '12'
            )
        );

        $savedOptions = $this->getElement()->getValue();
        if (! is_array($savedOptions)) {
            $savedOptions = array();
        }

        foreach ($savedOptions as $savedOption) {
            if (key_exists($savedOption['code'], $defaultOptions)) {
                unset($defaultOptions[$savedOption['code']]);
            }
        }

        // add not saved yet options
        foreach ($defaultOptions as $code => $defaultOption) {
            $option = array(
                'code' => $code,
                'label' => $defaultOption['label'],
                'count' => $defaultOption['count'],
                'amount_min' => '',
                'amount_max' => '',
                'rate' => $defaultOption['rate'],
                'cap' => $defaultOption['cap']
            );

            $savedOptions[uniqid('_' . $code . '_')] = $option;
        }

        $this->getElement()->setValue($savedOptions);
        return parent::getArrayRows();
    }
}
