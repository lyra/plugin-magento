<?php
/**
 * PayZen V2-Payment Module version 1.9.4 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyra\Payzen\Model\Api\Ws;

class GetPaymentDetailsResponse extends WsResponse
{
    /**
     * @var GetPaymentDetailsResult $getPaymentDetailsResult
     */
    private $getPaymentDetailsResult = null;

    /**
     * @return GetPaymentDetailsResult
     */
    public function getGetPaymentDetailsResult()
    {
        return $this->getPaymentDetailsResult;
    }

    /**
     * @param GetPaymentDetailsResult $getPaymentDetailsResult
     * @return GetPaymentDetailsResponse
     */
    public function setGetPaymentDetailsResult($getPaymentDetailsResult)
    {
        $this->getPaymentDetailsResult = $getPaymentDetailsResult;
        return $this;
    }
}
