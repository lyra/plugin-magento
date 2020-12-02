<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Source_Multi_PaymentCards
{
    protected $_allMultiCards = array(
        'AMEX', 'CB', 'DINERS', 'DISCOVER', 'E-CARTEBLEUE', 'JCB', 'MASTERCARD',
        'PRV_BDP', 'PRV_BDT', 'PRV_OPT', 'PRV_SOC', 'VISA', 'VISA_ELECTRON', 'VPAY'
    );

    public function toOptionArray()
    {
        $options = array();

        // Add ALL value at the beginning.
        $options[] = array('value' => '', 'label' => Mage::helper('payzen')->__('ALL'));

        foreach (Lyranetwork_Payzen_Model_Api_Api::getSupportedCardTypes() as $code => $name) {
            if (! in_array($code, $this->_allMultiCards)) {
                continue;
            }

            $options[] = array('value' => $code, 'label' => $code . ' - ' . $name);
        }

        return $options;
    }

    public function getMultiCards()
    {
        $options =  array();

        foreach (Lyranetwork_Payzen_Model_Api_Api::getSupportedCardTypes() as $code => $name) {
            if (! in_array($code, $this->_allMultiCards)) {
                continue;
            }

            $options[$code] = $name;
        }

        return $options;
    }
}
