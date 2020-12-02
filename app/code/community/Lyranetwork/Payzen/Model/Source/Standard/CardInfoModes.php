<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Source_Standard_CardInfoModes
{
    public function toOptionArray()
    {
        $options =  array();

        $embedded = array(
            Lyranetwork_Payzen_Helper_Data::MODE_EMBEDDED,
            Lyranetwork_Payzen_Helper_Data::MODE_POPIN
        );

        foreach (Mage::helper('payzen')->getConfigArray('card_info_modes') as $code => $name) {
            if ((in_array($code, $embedded)) && ! Lyranetwork_Payzen_Helper_Data::$pluginFeatures['embedded']) {
                // Payment with embedded fields option using REST API not available for all.
                continue;
            }

            $options[] = array(
                'value' => $code,
                'label' => Mage::helper('payzen')->__($name)
            );
        }

        return $options;
    }
}
