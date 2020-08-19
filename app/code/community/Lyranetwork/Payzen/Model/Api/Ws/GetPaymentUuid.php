<?php
/**
 * PayZen V2-Payment Module version 1.11.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class GetPaymentUuid
{
    /**
     * @var LegacyTransactionKeyRequest $legacyTransactionKeyRequest
     */
    private $legacyTransactionKeyRequest = null;

    /**
     * @return LegacyTransactionKeyRequest
     */
    public function getLegacyTransactionKeyRequest()
    {
        return $this->legacyTransactionKeyRequest;
    }

    /**
     * @param LegacyTransactionKeyRequest $legacyTransactionKeyRequest
     * @return GetPaymentUuid
     */
    public function setLegacyTransactionKeyRequest($legacyTransactionKeyRequest)
    {
        $this->legacyTransactionKeyRequest = $legacyTransactionKeyRequest;
        return $this;
    }
}
