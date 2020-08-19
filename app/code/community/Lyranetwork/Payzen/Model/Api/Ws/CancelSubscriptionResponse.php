<?php
/**
 * PayZen V2-Payment Module version 1.11.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CancelSubscriptionResponse extends WsResponse
{
    /**
     * @var CancelSubscriptionResult $cancelSubscriptionResult
     */
    private $cancelSubscriptionResult = null;

    /**
     * @return CancelSubscriptionResult
     */
    public function getCancelSubscriptionResult()
    {
        return $this->cancelSubscriptionResult;
    }

    /**
     * @param CancelSubscriptionResult $cancelSubscriptionResult
     * @return CancelSubscriptionResponse
     */
    public function setCancelSubscriptionResult($cancelSubscriptionResult)
    {
        $this->cancelSubscriptionResult = $cancelSubscriptionResult;
        return $this;
    }
}
