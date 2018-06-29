<?php
/**
 * PayZen V2-Payment Module version 1.9.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */

namespace Lyra\Payzen\Model\Api\Ws;

class ThreeDSMode
{
    const _DISABLED = 'DISABLED';
    const _ENABLED_CREATE = 'ENABLED_CREATE';
    const _ENABLED_FINALIZE = 'ENABLED_FINALIZE';
    const _MERCHANT_3DS = 'MERCHANT_3DS';
}
