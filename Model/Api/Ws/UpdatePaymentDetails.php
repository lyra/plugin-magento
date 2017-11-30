<?php
/**
 * PayZen V2-Payment Module version 2.1.3 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
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
