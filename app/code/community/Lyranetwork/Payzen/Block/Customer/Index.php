<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Block_Customer_Index extends Mage_Core_Block_Template
{
    public function getStoredPaymentMeans()
    {

        $means = array();

        $session = Mage::getSingleton('customer/session');

        // Customer not logged in.
        $customer = $session->getCustomer();
        if (! $customer || ! $session->isLoggedIn()) {
            return $means;
        }

        $aliasIds = array(
            'payzen_identifier' => 'payzen_masked_card',
            'payzen_sepa_identifier' => 'payzen_sepa_iban'
        );

        foreach ($aliasIds as $aliasId => $maskedId) {
            // Check if there is a saved alias.
            if (! Mage::helper('payzen/payment')->getCustomerAttribute($customer, $aliasId)) {
                continue;
            }

            $card = array();
            $card['alias'] = $aliasId;
            $card['pm'] = $maskedId;

            $maskedPan = Mage::helper('payzen/payment')->getCustomerAttribute($customer, $maskedId);
            $pos = strpos($maskedPan, '|');

            if ($pos !== false) {
                $card['brand'] = substr($maskedPan, 0, $pos);
                $card['number'] = substr($maskedPan, $pos + 1);
            } else {
                $card['brand'] = '';
                $card['number'] = $maskedPan;
            }

            $means[] = $card;
        }

        return $means;
    }

    public function getCcTypeImageSrc($card)
    {
        return Mage::getBlockSingleton('payzen/standard')->getCcTypeImageSrc($card);
    }
}

