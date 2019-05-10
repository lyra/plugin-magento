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

class RefundPaymentResponse extends WsResponse
{
    /**
     * @var RefundPaymentResult $refundPaymentResult
     */
    private $refundPaymentResult = null;

    /**
     * @return RefundPaymentResult
     */
    public function getRefundPaymentResult()
    {
        return $this->refundPaymentResult;
    }

    /**
     * @param RefundPaymentResult $refundPaymentResult
     * @return RefundPaymentResponse
     */
    public function setRefundPaymentResult($refundPaymentResult)
    {
        $this->refundPaymentResult = $refundPaymentResult;
        return $this;
    }
}
