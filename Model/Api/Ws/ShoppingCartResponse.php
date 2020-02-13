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

class ShoppingCartResponse
{
    /**
     * @var CartItemInfo[] $cartItemInfo
     */
    private $cartItemInfo = null;

    /**
     * @return CartItemInfo[]
     */
    public function getCartItemInfo()
    {
        return $this->cartItemInfo;
    }

    /**
     * @param CartItemInfo[] $cartItemInfo
     * @return ShoppingCartResponse
     */
    public function setCartItemInfo(array $cartItemInfo = null)
    {
        $this->cartItemInfo = $cartItemInfo;
        return $this;
    }
}
