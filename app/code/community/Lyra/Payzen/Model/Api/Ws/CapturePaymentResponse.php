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
