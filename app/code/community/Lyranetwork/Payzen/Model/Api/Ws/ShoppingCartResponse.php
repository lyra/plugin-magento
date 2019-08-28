<?php
/**
 * PayZen V2-Payment Module version 1.10.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
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
