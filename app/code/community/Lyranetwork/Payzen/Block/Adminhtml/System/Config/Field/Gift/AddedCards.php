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
 * Custom renderer for the add gift cards field.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Gift_AddedCards
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
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Gift_UploadButton
            )
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('payzen')->__('Add');

        parent::__construct();
    }
}
