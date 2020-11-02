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

class CancelCapturedPayment
{
    /**
     * @var CommonRequest $commonRequest
     */
    private $commonRequest = null;

    /**
     * @var QueryRequest $queryRequest
     */
    private $queryRequest = null;

    /**
     * @return CommonRequest
     */
    public function getCommonRequest()
    {
        return $this->commonRequest;
    }

    /**
     * @param CommonRequest $commonRequest
     * @return CancelCapturedPayment
     */
    public function setCommonRequest($commonRequest)
    {
        $this->commonRequest = $commonRequest;
        return $this;
    }

    /**
     * @return QueryRequest
     */
    public function getQueryRequest()
    {
        return $this->queryRequest;
    }

    /**
     * @param QueryRequest $queryRequest
     * @return CancelCapturedPayment
     */
    public function setQueryRequest($queryRequest)
    {
        $this->queryRequest = $queryRequest;
        return $this;
    }
}
