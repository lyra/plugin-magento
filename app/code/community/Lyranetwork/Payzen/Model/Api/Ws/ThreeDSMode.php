<?php
/**
 * PayZen V2-Payment Module version 1.11.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class ThreeDSMode
{
    const _DISABLED = 'DISABLED';
    const _ENABLED_CREATE = 'ENABLED_CREATE';
    const _ENABLED_FINALIZE = 'ENABLED_FINALIZE';
    const _MERCHANT_3DS = 'MERCHANT_3DS';
}
