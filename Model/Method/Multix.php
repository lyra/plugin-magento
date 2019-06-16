<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\Method;

/**
 * Virtual model for multiple method when payment option is selected.
 */
class Multix extends Multi
{

    /**
     * Check method for processing with base currency.
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        // This is a fictive payment method, allways return false to avoid method proposal.
        return false;
    }
}
