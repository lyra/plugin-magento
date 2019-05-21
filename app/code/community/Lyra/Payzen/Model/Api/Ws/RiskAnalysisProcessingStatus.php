<?php
/**
 * PayZen V2-Payment Module version 1.9.4 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyra\Payzen\Model\Api\Ws;

class RiskAnalysisProcessingStatus
{
    const _P_TO_SEND = 'P_TO_SEND';
    const _P_SEND_KO = 'P_SEND_KO';
    const _P_PENDING_AT_ANALYZER = 'P_PENDING_AT_ANALYZER';
    const _P_SEND_OK = 'P_SEND_OK';
    const _P_MANUAL = 'P_MANUAL';
    const _P_SKIPPED = 'P_SKIPPED';
    const _P_SEND_EXPIRED = 'P_SEND_EXPIRED';
}
