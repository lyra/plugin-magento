<?php
/**
 * PayZen V2-Payment Module version 1.10.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class GetPaymentUuidResponse extends WsResponse
{
    /**
     * @var LegacyTransactionKeyResult $legacyTransactionKeyResult
     */
    private $legacyTransactionKeyResult = null;

    /**
     * @return LegacyTransactionKeyResult
     */
    public function getLegacyTransactionKeyResult()
    {
        return $this->legacyTransactionKeyResult;
    }

    /**
     * @param LegacyTransactionKeyResult $legacyTransactionKeyResult
     * @return GetPaymentUuidResponse
     */
    public function setLegacyTransactionKeyResult($legacyTransactionKeyResult)
    {
        $this->legacyTransactionKeyResult = $legacyTransactionKeyResult;
        return $this;
    }
}
