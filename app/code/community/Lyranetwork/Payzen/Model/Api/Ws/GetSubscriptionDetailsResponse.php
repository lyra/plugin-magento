<?php
/**
 * PayZen V2-Payment Module version 1.10.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class GetSubscriptionDetailsResponse extends WsResponse
{
    /**
     * @var GetSubscriptionDetailsResult $getSubscriptionDetailsResult
     */
    private $getSubscriptionDetailsResult = null;

    /**
     * @return GetSubscriptionDetailsResult
     */
    public function getGetSubscriptionDetailsResult()
    {
        return $this->getSubscriptionDetailsResult;
    }

    /**
     * @param GetSubscriptionDetailsResult $getSubscriptionDetailsResult
     * @return GetSubscriptionDetailsResponse
     */
    public function setGetSubscriptionDetailsResult($getSubscriptionDetailsResult)
    {
        $this->getSubscriptionDetailsResult = $getSubscriptionDetailsResult;
        return $this;
    }
}
