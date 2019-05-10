<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyra_Payzen_Model_Source_Gift_GiftCards
{
    public function toOptionArray()
    {
        $options =  array();

        foreach (Mage::getModel('payzen/payment_gift')->getSupportedGcTypes() as $code => $name) {
            $options[] = array(
                'value' => $code,
                'label' => $name
            );
        }

        return $options;
    }
}
