<?php
/**
 * PayZen V2-Payment Module version 2.4.11 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class GetSubscriptionDetails
{
    /**
     * @var QueryRequest $queryRequest
     */
    private $queryRequest = null;

    /**
     * @return QueryRequest
     */
    public function getQueryRequest()
    {
        return $this->queryRequest;
    }

    /**
     * @param QueryRequest $queryRequest
     * @return GetSubscriptionDetails
     */
    public function setQueryRequest($queryRequest)
    {
        $this->queryRequest = $queryRequest;
        return $this;
    }
}
