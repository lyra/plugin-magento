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
 * Custom renderer for the shipping options field.
 */
class Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_ShipOptions
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn(
            'title',
            array(
                'label' => Mage::helper('payzen')->__('Method title'),
                'style' => 'width: 210px;',
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_Label
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
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_List($options)
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
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_List($options)
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
                'renderer' => new Lyranetwork_Payzen_Block_Adminhtml_System_Config_Field_Column_List($options)
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
        $methods = $this->_getAllShippingMethods();

        $savedMethods = $this->getElement()->getValue();
        if ($savedMethods && is_array($savedMethods) && ! empty($savedMethods)) {
            foreach ($savedMethods as $id => $method) {
                if (key_exists($method['code'], $methods)) {
                    // Add methods current title.
                    $method['title'] = $methods[$method['code']];
                    $value[$id] = $method;

                    unset($methods[$method['code']]);
                }
            }
        }

        // Add not saved yet methods.
        if ($methods && is_array($methods) && ! empty($methods)) {
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

        // List of all configured carriers.
        $carriers = Mage::getSingleton('shipping/config')->getAllCarriers($store);

        foreach ($carriers as $carrierCode => $carrierModel) {
            $carrierModel->setStore($store);

            // Filter carriers to get active ones on current scope.
            if (! $carrierModel->isActive()) {
                continue;
            }

            try {
                $carrierMethods = $carrierModel->getAllowedMethods();
                if (! $carrierMethods) {
                    continue;
                }

                foreach ($carrierMethods as $methodCode => $methodTitle) {
                    $code = $carrierCode . '_' . $methodCode;

                    $title = '[' . $carrierCode . '] ';
                    if (is_string($methodTitle) && ! empty($methodTitle)) {
                        $title .= $methodTitle;
                    } else { // Non standard method title.
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
                document.observe("dom:loaded", function() {
                    $$(
                        "select.payzen_list_type",
                        "select.payzen_list_speed",
                        "select.payzen_list_delay"
                    ).each(function(elt) {
                        var value = elt.readAttribute("currentvalue");

                        // Option to select.
                        var opt = elt.select("option[value=\"" + value + "\"]");
                        if (opt && opt.length > 0) {
                            opt[0].selected = true;
                            return false;
                        }
                    });

                    // Enable delay select for rows with speed equals PRIORITY.
                    $$("select.payzen_list_delay").each(function(elt) {
                        var speedName = elt.name.replace("[delay]", "[speed]");

                        // Select by name returns one element.
                        var speedElt = $$("select[name=\"" + speedName + "\"]")[0];

                        if (speedElt.value === "PRIORITY") {
                            elt.enable();
                        } else {
                            elt.disable();
                        }
                    });

                    $$("select.payzen_list_speed").invoke("observe", "change", function() {
                        var delayName = this.name.replace("[speed]", "[delay]");

                        // Select by name returns one element.
                        var elt = $$("select[name=\"" + delayName + "\"]")[0];

                        if (this.value === "PRIORITY") {
                            elt.enable();
                        } else {
                            elt.disable();
                        }
                    });
                });
                </script>';

        return parent::_toHtml() . "\n" . $script;
    }
}
