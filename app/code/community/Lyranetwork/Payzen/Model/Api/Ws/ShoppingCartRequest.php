<?php
/**
 * PayZen V2-Payment Module version 1.10.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class ShoppingCartRequest
{
    /**
     * @var int $insuranceAmount
     */
    private $insuranceAmount = null;

    /**
     * @var int $shippingAmount
     */
    private $shippingAmount = null;

    /**
     * @var int $taxAmount
     */
    private $taxAmount = null;

    /**
     * @var CartItemInfo[] $cartItemInfo
     */
    private $cartItemInfo = null;

    /**
     * @return int
     */
    public function getInsuranceAmount()
    {
        return $this->insuranceAmount;
    }

    /**
     * @param int $insuranceAmount
     * @return ShoppingCartRequest
     */
    public function setInsuranceAmount($insuranceAmount)
    {
        $this->insuranceAmount = $insuranceAmount;
        return $this;
    }

    /**
     * @return int
     */
    public function getShippingAmount()
    {
        return $this->shippingAmount;
    }

    /**
     * @param int $shippingAmount
     * @return ShoppingCartRequest
     */
    public function setShippingAmount($shippingAmount)
    {
        $this->shippingAmount = $shippingAmount;
        return $this;
    }

    /**
     * @return int
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    /**
     * @param int $taxAmount
     * @return ShoppingCartRequest
     */
    public function setTaxAmount($taxAmount)
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    /**
     * @return CartItemInfo[]
     */
    public function getCartItemInfo()
    {
        return $this->cartItemInfo;
    }

    /**
     * @param CartItemInfo[] $cartItemInfo
     * @return ShoppingCartRequest
     */
    public function setCartItemInfo(array $cartItemInfo)
    {
        $this->cartItemInfo = $cartItemInfo;
        return $this;
    }
}
