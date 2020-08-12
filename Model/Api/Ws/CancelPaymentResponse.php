<?php
/**
 * PayZen V2-Payment Module version 2.4.9 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CancelPaymentResponse extends WsResponse
{
    /**
     * @var CancelPaymentResult $cancelPaymentResult
     */
    private $cancelPaymentResult = null;

    /**
     * @return CancelPaymentResult
     */
    public function getCancelPaymentResult()
    {
        return $this->cancelPaymentResult;
    }

    /**
     * @param CancelPaymentResult $cancelPaymentResult
     * @return CancelPaymentResponse
     */
    public function setCancelPaymentResult($cancelPaymentResult)
    {
        $this->cancelPaymentResult = $cancelPaymentResult;
        return $this;
    }
}
