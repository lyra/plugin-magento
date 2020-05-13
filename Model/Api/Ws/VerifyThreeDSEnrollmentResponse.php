<?php
/**
 * PayZen V2-Payment Module version 2.4.6 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class VerifyThreeDSEnrollmentResponse extends WsResponse
{
    /**
     * @var VerifyThreeDSEnrollmentResult $verifyThreeDSEnrollmentResult
     */
    private $verifyThreeDSEnrollmentResult = null;

    /**
     * @return VerifyThreeDSEnrollmentResult
     */
    public function getVerifyThreeDSEnrollmentResult()
    {
        return $this->verifyThreeDSEnrollmentResult;
    }

    /**
     * @param VerifyThreeDSEnrollmentResult $verifyThreeDSEnrollmentResult
     * @return VerifyThreeDSEnrollmentResponse
     */
    public function setVerifyThreeDSEnrollmentResult($verifyThreeDSEnrollmentResult)
    {
        $this->verifyThreeDSEnrollmentResult = $verifyThreeDSEnrollmentResult;
        return $this;
    }
}
