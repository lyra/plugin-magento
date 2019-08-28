<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Source_PaymentCards
{
    public function toOptionArray()
    {
        $options =  array();
        $options[] = array (
            'value' => '',
            'label' => Mage::helper('payzen')->__('ALL')
        );

        foreach (Lyranetwork_Payzen_Model_Api_Api::getSupportedCardTypes() as $code => $name) {
            if ($code === 'ONEY_SANDBOX' || $code === 'ONEY') {
                continue;
            }

            $options[] = array (
                'value' => $code,
                'label' => $name
            );
        }

        return $options;
    }
}
