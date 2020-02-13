<?php
/**
 * PayZen V2-Payment Module version 2.4.4 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CapturePaymentResponse extends WsResponse
{
    /**
     * @var CapturePaymentResult $capturePaymentResult
     */
    private $capturePaymentResult = null;

    /**
     * @return CapturePaymentResult
     */
    public function getCapturePaymentResult()
    {
        return $this->capturePaymentResult;
    }

    /**
     * @param CapturePaymentResult $capturePaymentResult
     * @return CapturePaymentResponse
     */
    public function setCapturePaymentResult($capturePaymentResult)
    {
        $this->capturePaymentResult = $capturePaymentResult;
        return $this;
    }
}
