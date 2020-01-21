<?php
/**
 * PayZen V2-Payment Module version 2.4.3 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class FindPaymentsResponse extends WsResponse
{
    /**
     * @var FindPaymentsResult $findPaymentsResult
     */
    private $findPaymentsResult = null;

    /**
     * @return FindPaymentsResult
     */
    public function getFindPaymentsResult()
    {
        return $this->findPaymentsResult;
    }

    /**
     * @param FindPaymentsResult $findPaymentsResult
     * @return FindPaymentsResponse
     */
    public function setFindPaymentsResult($findPaymentsResult)
    {
        $this->findPaymentsResult = $findPaymentsResult;
        return $this;
    }
}
