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
 * Virtual model for multiple method when payment option is selected.
 */
class Lyra_Payzen_Model_Payment_Multix extends Lyra_Payzen_Model_Payment_Multi
{
    /**
     * Check method for processing with base currency
     *
     * @param  string $baseCurrencyCode
     * @return boolean
     */
    public function canUseForCurrency($baseCurrencyCode)
    {
        // This is a fictive payment method, allways return false to avoid method proposal.
        return false;
    }
}
