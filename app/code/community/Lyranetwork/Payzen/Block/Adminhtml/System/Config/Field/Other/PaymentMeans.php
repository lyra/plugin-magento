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
 * Custom renderer for the other payment means field.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Other_PaymentMeans
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn(
            'label',
            array(
                'label' => Mage::helper('payzen')->__('Label'),
                'style' => 'width: 150px;'
            )
        );

        $cards = Lyranetwork_Payzen_Model_Api_Api::getSupportedCardTypes();

        foreach ($cards as $code => $label) {
            $cards[$code] = $code . " - " . $label;
        }

        $options = array('options' => $cards);

        $this->addColumn(
            'means',
            array(
                'label' => Mage::helper('payzen')->__('Means of payment'),
                'style' => 'width: 100px;',
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_List($options)
            )
        );

        $this->addColumn(
            'countries',
            array(
                'label' => Mage::helper('payzen')->__('Countries'),
                'style' => 'width: 100px;',
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_Multiselect($this->getCountries())
            )
        );

        $this->addColumn(
            'minimum',
            array(
                'label' => Mage::helper('payzen')->__('Min. amount'),
                'style' => 'width: 100px;'
            )
        );

        $this->addColumn(
            'maximum',
            array(
                'label' => Mage::helper('payzen')->__('Max. amount'),
                'style' => 'width: 100px;'
            )
        );

        $this->addColumn(
            'capture_delay',
            array(
                'label' => Mage::helper('payzen')->__('Capture delay'),
                'style' => 'width: 100px;'
            )
        );

        $args = $this->getValidationModes();
        $args['default'] = '-1';

        $this->addColumn(
            'validation_mode',
            array(
                'label' => Mage::helper('payzen')->__('Validation mode'),
                'style' => 'width: 100px;',
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_List($args)
            )
        );

        $args = $this->yesno();
        $args['default'] = '0';

        $this->addColumn(
            'cart_data',
            array(
                'label' => Mage::helper('payzen')->__('Cart data'),
                'style' => 'width: 50px;',
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_List($args)
            )
         );

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('payzen')->__('Add');
        parent::__construct();
    }

    public function getCountries()
    {
        $magentoCountries = Mage::getResourceModel('directory/country_collection')
            ->loadData()
            ->toOptionArray(false);

        $countries = array();
        foreach ($magentoCountries as $country) {
            $countries[$country['value']] = $country['label'];
        }

        return array('options' => $countries);
    }

    public function getValidationModes()
    {
        $options =  array();

        foreach (Mage::helper('payzen')->getConfigArray('validation_modes') as $code => $name) {
            $options[$code] = Mage::helper('payzen')->__($name);
        }

        return array('options' => $options);
    }

    public function yesno()
    {
        $options = array(
            '0' => Mage::helper('payzen')->__('No'),
            '1' => Mage::helper('payzen')->__('Yes')
        );

        return array('options' => $options);
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $script = '<script type="text/javascript">
                //<![CDATA[
                document.observe("dom:loaded", function() {
                    $$("select.payzen_list_means, select.payzen_list_validation_mode, select.payzen_list_cart_data").each(function(elt) {
                        var value = elt.readAttribute("currentvalue") || elt.readAttribute("defaultvalue");

                        // Option to select.
                        var opt = elt.select("option[value=\"" + value + "\"]");
                        if (opt && opt.length > 0) {
                            opt[0].selected = true;
                            return false;
                        }
                    });

                    $$("select.payzen_multiselect_countries").each(function(elt) {
                        var values = elt.readAttribute("currentvalue").split(",");

                        for(var i = 0; i < values.length; i++) {
                            // Options to select.
                            var opt = elt.select("option[value=\"" + values[i] + "\"]");
                            if (opt && opt.length > 0) {
                                opt[0].selected = true;
                            }
                        }
                    });
                });
                //]]>
                </script>';

        return parent::_toHtml() . "\n" . $script;
    }
}
