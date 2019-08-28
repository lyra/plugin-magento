<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Source_ValidationModes extends Varien_Object
{
    public function toOptionArray()
    {
        $options =  array();

        foreach (Mage::helper('payzen')->getConfigArray('validation_modes') as $code => $name) {
            if (($this->getPath() === 'payment/payzen/validation_mode') && ($code === -1)) {
                continue;
            }

            $options[] = array (
                'value' => (string) $code,
                'label' => Mage::helper('payzen')->__($name)
            );
        }

        return $options;
    }
}
