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

class UpdatePaymentDetails
{
    /**
     * @var QueryRequest $queryRequest
     */
    private $queryRequest = null;

    /**
     * @var ShoppingCartRequest $shoppingCartRequest
     */
    private $shoppingCartRequest = null;

    /**
     * @return QueryRequest
     */
    public function getQueryRequest()
    {
        return $this->queryRequest;
    }

    /**
     * @param QueryRequest $queryRequest
     * @return UpdatePaymentDetails
     */
    public function setQueryRequest($queryRequest)
    {
        $this->queryRequest = $queryRequest;
        return $this;
    }

    /**
     * @return ShoppingCartRequest
     */
    public function getShoppingCartRequest()
    {
        return $this->shoppingCartRequest;
    }

    /**
     * @param ShoppingCartRequest $shoppingCartRequest
     * @return UpdatePaymentDetails
     */
    public function setShoppingCartRequest($shoppingCartRequest)
    {
        $this->shoppingCartRequest = $shoppingCartRequest;
        return $this;
    }
}
