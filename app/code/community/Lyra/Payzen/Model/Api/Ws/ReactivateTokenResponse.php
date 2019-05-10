<?php
/**
 * PayZen V2-Payment Module version 1.9.3 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyra\Payzen\Model\Api\Ws;

class ReactivateTokenResponse extends WsResponse
{
    /**
     * @var ReactivateTokenResult $reactivateTokenResult
     */
    private $reactivateTokenResult = null;

    /**
     * @return ReactivateTokenResult
     */
    public function getReactivateTokenResult()
    {
        return $this->reactivateTokenResult;
    }

    /**
     * @param ReactivateTokenResult $reactivateTokenResult
     * @return ReactivateTokenResponse
     */
    public function setReactivateTokenResult($reactivateTokenResult)
    {
        $this->reactivateTokenResult = $reactivateTokenResult;
        return $this;
    }
}
