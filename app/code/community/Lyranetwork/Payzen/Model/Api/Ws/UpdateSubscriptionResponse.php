<?php
/**
 * PayZen V2-Payment Module version 1.11.3 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class UpdateSubscriptionResponse extends WsResponse
{
    /**
     * @var UpdateSubscriptionResult $updateSubscriptionResult
     */
    private $updateSubscriptionResult = null;

    /**
     * @return UpdateSubscriptionResult
     */
    public function getUpdateSubscriptionResult()
    {
        return $this->updateSubscriptionResult;
    }

    /**
     * @param UpdateSubscriptionResult $updateSubscriptionResult
     * @return UpdateSubscriptionResponse
     */
    public function setUpdateSubscriptionResult($updateSubscriptionResult)
    {
        $this->updateSubscriptionResult = $updateSubscriptionResult;
        return $this;
    }
}
