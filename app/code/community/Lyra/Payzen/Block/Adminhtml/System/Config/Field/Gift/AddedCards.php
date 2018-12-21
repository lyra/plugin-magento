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
 * Custom renderer for the PayZen add gift cards field
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Field_Gift_AddedCards
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn(
            'code',
            array(
                'label' => Mage::helper('payzen')->__('Card code'),
                'style' => 'width: 100px;'
            )
        );
        $this->addColumn(
            'name',
            array(
                'label' => Mage::helper('payzen')->__('Card label'),
                'style' => 'width: 180px;'
            )
        );
        $this->addColumn(
            'logo',
            array(
                'label' => Mage::helper('payzen')->__('Card logo'),
                'style' => 'width: 340px;',
                'size' => '20',
                'renderer' => new Lyra_Payzen_Block_Adminhtml_System_Config_Field_Gift_UploadButton
            )
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('payzen')->__('Add');

        parent::__construct();
    }
}
