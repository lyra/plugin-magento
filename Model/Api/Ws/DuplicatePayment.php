<?php
/**
 * PayZen V2-Payment Module version 2.4.0 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class DuplicatePayment
{
    /**
     * @var CommonRequest $commonRequest
     */
    private $commonRequest = null;

    /**
     * @var PaymentRequest $paymentRequest
     */
    private $paymentRequest = null;

    /**
     * @var OrderRequest $orderRequest
     */
    private $orderRequest = null;

    /**
     * @var QueryRequest $queryRequest
     */
    private $queryRequest = null;

    /**
     * @return CommonRequest
     */
    public function getCommonRequest()
    {
        return $this->commonRequest;
    }

    /**
     * @param CommonRequest $commonRequest
     * @return DuplicatePayment
     */
    public function setCommonRequest($commonRequest)
    {
        $this->commonRequest = $commonRequest;
        return $this;
    }

    /**
     * @return PaymentRequest
     */
    public function getPaymentRequest()
    {
        return $this->paymentRequest;
    }

    /**
     * @param PaymentRequest $paymentRequest
     * @return DuplicatePayment
     */
    public function setPaymentRequest($paymentRequest)
    {
        $this->paymentRequest = $paymentRequest;
        return $this;
    }

    /**
     * @return OrderRequest
     */
    public function getOrderRequest()
    {
        return $this->orderRequest;
    }

    /**
     * @param OrderRequest $orderRequest
     * @return DuplicatePayment
     */
    public function setOrderRequest($orderRequest)
    {
        $this->orderRequest = $orderRequest;
        return $this;
    }

    /**
     * @return QueryRequest
     */
    public function getQueryRequest()
    {
        return $this->queryRequest;
    }

    /**
     * @param QueryRequest $queryRequest
     * @return DuplicatePayment
     */
    public function setQueryRequest($queryRequest)
    {
        $this->queryRequest = $queryRequest;
        return $this;
    }
}
