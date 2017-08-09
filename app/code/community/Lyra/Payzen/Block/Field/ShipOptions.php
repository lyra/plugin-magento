<?php
/**
 * PayZen V2-Payment Module version 1.7.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Custom renderer for the PayZen shipping options field.
 */
class Lyra_Payzen_Block_Field_ShipOptions extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn(
            'title',
            array(
                'label' => Mage::helper('payzen')->__('Method title'),
                'style' => 'width: 210px;',
                'renderer' => new Lyra_Payzen_Block_Field_Column_Label
            )
        );
        $this->addColumn(
            'oney_label',
            array(
                'label' => Mage::helper('payzen')->__('Name'),
                'style' => 'width: 210px;'
            )
        );

        $options = array(
            'options' => array(
                'PACKAGE_DELIVERY_COMPANY' => 'Delivery company',
                'RECLAIM_IN_SHOP' => 'Reclaim in shop',
                'RELAY_POINT' => 'Relay point',
                'RECLAIM_IN_STATION' => 'Reclaim in station'
            )
        );
        $this->addColumn(
            'type',
            array(
                'label' => Mage::helper('payzen')->__('Type'),
                'style' => 'width: 130px;',
                'renderer' => new Lyra_Payzen_Block_Field_Column_List($options)
            )
        );

        $options = array(
            'options' => array(
                'STANDARD' => 'Standard',
                'EXPRESS' => 'Express',
                'PRIORITY' => 'Priority'
            )
        );
        $this->addColumn(
            'speed',
            array(
                'label' => Mage::helper('payzen')->__('Rapidity'),
                'style' => 'width: 75px;',
                'renderer' => new Lyra_Payzen_Block_Field_Column_List($options)
            )
        );

        $options = array(
            'options' => array(
                'INFERIOR_EQUALS' => '<= 1 hour',
                'SUPERIOR' => '> 1 hour',
                'IMMEDIATE' => 'Immediate',
                'ALWAYS' => '24/7'
            )
        );
        $this->addColumn(
            'delay',
            array(
                'label' => Mage::helper('payzen')->__('Delay'),
                'style' => 'width: 90px;',
                'renderer' => new Lyra_Payzen_Block_Field_Column_List($options)
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
        $value = array();

        /** @var array[string][string] $methods */
        $methods = $this->_getAllShippingMethods();

        $savedMethods = $this->getElement()->getValue();
        if ($savedMethods && is_array($savedMethods) && !empty($savedMethods)) {
            foreach ($savedMethods as $id => $method) {
                if (key_exists($method['code'], $methods)) {
                    // add methods current title
                    $method['title'] = $methods[$method['code']];
                    $value[$id] = $method;

                    unset($methods[$method['code']]);
                }
            }
        }

        // add not saved yet methods
        if ($methods && is_array($methods) && !empty($methods)) {
            foreach ($methods as $code => $name) {
                $value[uniqid('_' . $code . '_')] = array(
                        'code' => $code,
                        'title' => $name,
                        'oney_label' => Mage::helper('payzen/util')->normalizeShipMethodName($name),
                        'type' => 'PACKAGE_DELIVERY_COMPANY',
                        'speed' => 'STANDARD',
                        'mark' => '*'
                );
            }
        }

        $this->getElement()->setValue($value);
        return parent::getArrayRows();
    }

    protected function _getAllShippingMethods()
    {
        $options = array();

        $configDataModel = Mage::getSingleton('adminhtml/config_data');

        $store = null;
        if ($configDataModel->getScope() === 'stores') {
            $store = $configDataModel->getStore();
        }

        // list of all configured carriers
        $carriers = Mage::getSingleton('shipping/config')->getAllCarriers($store);

        foreach ($carriers as $carrierCode => $carrierModel) {
            $carrierModel->setStore($store);

            // filter carriers to get active ones on current scope
            if (!$carrierModel->isActive()) {
                continue;
            }

            try {
                $carrierMethods = $carrierModel->getAllowedMethods();
                if (!$carrierMethods) {
                    continue;
                }

                // $carrierTitle = $carrierModel->getConfigData('title');
                foreach ($carrierMethods as $methodCode => $methodTitle) {
                    $code = $carrierCode . '_' . $methodCode;

                    $title = '[' . $carrierCode . '] ';
                    if (is_string($methodTitle) && !empty($methodTitle)) {
                        $title .= $methodTitle;
                    } else { // non standard method title
                        $title .= $methodCode;
                    }

                    $options[$code] = $title;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return $options;
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
                    $$(
                        "select.payzen_list_type",
                        "select.payzen_list_speed",
                        "select.payzen_list_delay"
                    ).each(function(elt) {
                        var value = elt.readAttribute("currentvalue");

                        // option to select
                        var opt = elt.select("option[value=\"" + value + "\"]");
                        if (opt && opt.length > 0) {
                            opt[0].selected = true;
                            return false;
                        }
                    });

                    // enable delay select for rows with speed == PRIORITY & type = RECLAIM_IN_SHOP
                    $$("select.payzen_list_delay").each(function(elt) {
                        var speedName = elt.name.replace("[delay]", "[speed]");
                        var typeName = elt.name.replace("[delay]", "[type]");

                        // select by name returns one element
                        var speedElt = $$("select[name=\"" + speedName + "\"]")[0];
                        var typeElt = $$("select[name=\"" + typeName + "\"]")[0];

                        if (speedElt.value == "PRIORITY" && typeElt.value == "RECLAIM_IN_SHOP") {
                            elt.enable();
                        } else {
                            elt.disable();
                        }
                    });

                    $$("select.payzen_list_speed").invoke("observe", "change", function() {
                        var delayName = this.name.replace("[speed]", "[delay]");
                        var typeName = this.name.replace("[speed]", "[type]");

                        // select by name returns one element
                        var elt = $$("select[name=\"" + delayName + "\"]")[0];
                        var typeElt = $$("select[name=\"" + typeName + "\"]")[0];

                        if (this.value == "PRIORITY" && typeElt.value == "RECLAIM_IN_SHOP") {
                            elt.enable();
                        } else {
                            elt.disable();
                        }
                    });

                    $$("select.payzen_list_type").invoke("observe", "change", function() {
                        var delayName = this.name.replace("[type]", "[delay]");
                        var speedName = this.name.replace("[type]", "[speed]");

                        // select by name returns one element
                        var elt = $$("select[name=\"" + delayName + "\"]")[0];
                        var speedElt = $$("select[name=\"" + speedName + "\"]")[0];

                        if (speedElt.value == "PRIORITY" && this.value == "RECLAIM_IN_SHOP") {
                            elt.enable();
                        } else {
                            elt.disable();
                        }
                    });
                });
                //]]>
                </script>';

        return parent::_toHtml() . "\n" . $script;
    }
}
