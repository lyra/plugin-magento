<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Source_Multi_CardInfoModes
{
    public function toOptionArray()
    {
        $options =  array();

        foreach (Mage::helper('payzen')->getConfigArray('multi_card_info_modes') as $code => $name) {
            $options[] = array(
                'value' => $code,
                'label' => Mage::helper('payzen')->__($name)
            );
        }

        return $options;
    }
}
