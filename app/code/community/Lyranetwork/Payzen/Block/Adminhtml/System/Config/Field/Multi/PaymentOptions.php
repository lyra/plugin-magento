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
 * Custom renderer for the multi payment options field.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Multi_PaymentOptions
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn(
            'label',
            array(
                'label' => Mage::helper('payzen')->__('Label'),
                'style' => 'width: 150px;',
            )
        );
        $this->addColumn(
            'minimum',
            array(
                'label' => Mage::helper('payzen')->__('Min. amount'),
                'style' => 'width: 80px;',
            )
        );
        $this->addColumn(
            'maximum',
            array(
                'label' => Mage::helper('payzen')->__('Max. amount'),
                'style' => 'width: 80px;',
            )
        );

        $cards = Lyranetwork_Payzen_Model_Api_Api::getSupportedCardTypes();
        if (isset($cards['CB'])) {
            // If CB is available, we allow contract override.
            $this->addColumn(
                'contract',
                array(
                    'label' => Mage::helper('payzen')->__('Contract'),
                    'style' => 'width: 65px;',
                )
            );
        }

        $this->addColumn(
            'count',
            array(
                'label' => Mage::helper('payzen')->__('Count'),
                'style' => 'width: 65px;',
            )
        );
        $this->addColumn(
            'period',
            array(
                'label' => Mage::helper('payzen')->__('Period'),
                'style' => 'width: 65px;',
            )
        );
        $this->addColumn(
            'first',
            array(
                'label' => Mage::helper('payzen')->__('1st installment'),
                'style' => 'width: 70px;',
            )
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('payzen')->__('Add');
        parent::__construct();
    }
}
