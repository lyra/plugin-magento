<?php
/**
 * PayZen V2-Payment Module version 2.4.1 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class UpdateTokenResponse extends WsResponse
{
    /**
     * @var UpdateTokenResult $updateTokenResult
     */
    private $updateTokenResult = null;

    /**
     * @return UpdateTokenResult
     */
    public function getUpdateTokenResult()
    {
        return $this->updateTokenResult;
    }

    /**
     * @param UpdateTokenResult $updateTokenResult
     * @return UpdateTokenResponse
     */
    public function setUpdateTokenResult($updateTokenResult)
    {
        $this->updateTokenResult = $updateTokenResult;
        return $this;
    }
}
