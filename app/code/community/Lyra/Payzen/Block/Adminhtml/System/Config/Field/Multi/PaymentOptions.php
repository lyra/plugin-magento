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
 * Custom renderer for the PayZen multi payment options field
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Field_Multi_PaymentOptions
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

        $cards = Lyra_Payzen_Model_Api_Api::getSupportedCardTypes();
        if (isset($cards['CB'])) {
            // if CB is available, we allow contract override.
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
                'label' => Mage::helper('payzen')->__('1st payment'),
                'style' => 'width: 70px;',
            )
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('payzen')->__('Add');
        parent::__construct();
    }
}
