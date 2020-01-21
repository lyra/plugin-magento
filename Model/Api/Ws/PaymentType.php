<?php
/**
 * PayZen V2-Payment Module version 2.4.3 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class PaymentType
{
    const _SINGLE = 'SINGLE';
    const _INSTALLMENT = 'INSTALLMENT';
    const _SPLIT = 'SPLIT';
    const _SUBSCRIPTION = 'SUBSCRIPTION';
    const _RETRY = 'RETRY';
}
