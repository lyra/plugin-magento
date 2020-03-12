<?php
/**
 * PayZen V2-Payment Module version 1.11.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class GetPaymentDetails
{
    /**
     * @var QueryRequest $queryRequest
     */
    private $queryRequest = null;

    /**
     * @var ExtendedResponseRequest $extendedResponseRequest
     */
    private $extendedResponseRequest = null;

    /**
     * @return QueryRequest
     */
    public function getQueryRequest()
    {
        return $this->queryRequest;
    }

    /**
     * @param QueryRequest $queryRequest
     * @return GetPaymentDetails
     */
    public function setQueryRequest($queryRequest)
    {
        $this->queryRequest = $queryRequest;
        return $this;
    }

    /**
     * @return ExtendedResponseRequest
     */
    public function getExtendedResponseRequest()
    {
        return $this->extendedResponseRequest;
    }

    /**
     * @param ExtendedResponseRequest $extendedResponseRequest
     * @return GetPaymentDetails
     */
    public function setExtendedResponseRequest($extendedResponseRequest)
    {
        $this->extendedResponseRequest = $extendedResponseRequest;
        return $this;
    }
}
