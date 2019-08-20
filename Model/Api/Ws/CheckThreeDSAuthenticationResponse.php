<?php
/**
 * PayZen V2-Payment Module version 2.4.2 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CheckThreeDSAuthenticationResponse extends WsResponse
{
    /**
     * @var CheckThreeDSAuthenticationResult $checkThreeDSAuthenticationResult
     */
    private $checkThreeDSAuthenticationResult = null;

    /**
     * @return CheckThreeDSAuthenticationResult
     */
    public function getCheckThreeDSAuthenticationResult()
    {
        return $this->checkThreeDSAuthenticationResult;
    }

    /**
     * @param CheckThreeDSAuthenticationResult $checkThreeDSAuthenticationResult
     * @return CheckThreeDSAuthenticationResponse
     */
    public function setCheckThreeDSAuthenticationResult($checkThreeDSAuthenticationResult)
    {
        $this->checkThreeDSAuthenticationResult = $checkThreeDSAuthenticationResult;
        return $this;
    }
}
