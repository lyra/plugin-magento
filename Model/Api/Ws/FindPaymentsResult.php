<?php
/**
 * PayZen V2-Payment Module version 2.4.11 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class FindPaymentsResult
{
    /**
     * @var CommonResponse $commonResponse
     */
    private $commonResponse = null;

    /**
     * @var OrderResponse $orderResponse
     */
    private $orderResponse = null;

    /**
     * @var TransactionItem $transactionItem
     */
    private $transactionItem = null;

    /**
     * @return CommonResponse
     */
    public function getCommonResponse()
    {
        return $this->commonResponse;
    }

    /**
     * @param CommonResponse $commonResponse
     * @return FindPaymentsResult
     */
    public function setCommonResponse($commonResponse)
    {
        $this->commonResponse = $commonResponse;
        return $this;
    }

    /**
     * @return OrderResponse
     */
    public function getOrderResponse()
    {
        return $this->orderResponse;
    }

    /**
     * @param OrderResponse $orderResponse
     * @return FindPaymentsResult
     */
    public function setOrderResponse($orderResponse)
    {
        $this->orderResponse = $orderResponse;
        return $this;
    }

    /**
     * @return TransactionItem
     */
    public function getTransactionItem()
    {
        return $this->transactionItem;
    }

    /**
     * @param TransactionItem $transactionItem
     * @return FindPaymentsResult
     */
    public function setTransactionItem($transactionItem)
    {
        $this->transactionItem = $transactionItem;
        return $this;
    }
}
