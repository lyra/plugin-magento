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
 * Custom renderer for FacilyPay Oney payment options field.
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Field_Oney_PaymentOptions
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
            'code',
            array(
                'label' => Mage::helper('payzen')->__('Code'),
                'style' => 'width: 100px;'
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
        $this->addColumn(
            'count',
            array(
                'label' => Mage::helper('payzen')->__('Count'),
                'style' => 'width: 65px;',
            )
        );
        $this->addColumn(
            'rate',
            array(
                'label' => Mage::helper('payzen')->__('Rate'),
                'style' => 'width: 65px;',
            )
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('payzen')->__('Add');

        parent::__construct();
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $script = '';

        if ($this->getElement()->getCanUseWebsiteValue() || $this->getElement()->getCanUseDefaultValue()) {
            $script .= '
                <script type="text/javascript">
                //<![CDATA[
                  document.observe("dom:loaded", function() {';

            if ($this->getElement()->getDisabled()) {
                $script .= '
                    toggleValueElements({checked: true}, $("payment_payzen_oney_oney_payment_options").parentNode);';
            }

            $script .= '
                    Event.observe($("payment_payzen_oney_oney_enable_payment_options"), "change", function() {
                      toggleValueElements(
                        $("payment_payzen_oney_oney_payment_options_inherit"),
                        $("payment_payzen_oney_oney_payment_options").parentNode
                      );
                    });
                  });
                //]]>
                </script>';
        }

        return '<div id="' . $this->getElement()->getId() . '">' . parent::_toHtml() . "\n" . $script . '</div>';
    }
}
