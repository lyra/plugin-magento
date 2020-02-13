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

class CreateSubscriptionResponse extends WsResponse
{
    /**
     * @var CreateSubscriptionResult $createSubscriptionResult
     */
    private $createSubscriptionResult = null;

    /**
     * @return CreateSubscriptionResult
     */
    public function getCreateSubscriptionResult()
    {
        return $this->createSubscriptionResult;
    }

    /**
     * @param CreateSubscriptionResult $createSubscriptionResult
     * @return CreateSubscriptionResponse
     */
    public function setCreateSubscriptionResult($createSubscriptionResult)
    {
        $this->createSubscriptionResult = $createSubscriptionResult;
        return $this;
    }
}
