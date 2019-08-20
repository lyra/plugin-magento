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
