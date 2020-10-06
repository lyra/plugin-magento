<?php
/**
 * PayZen V2-Payment Module version 2.4.10 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CartItemInfo
{
    /**
     * @var string $productLabel
     */
    private $productLabel = null;

    /**
     * @var ProductType $productType
     */
    private $productType = null;

    /**
     * @var string $productRef
     */
    private $productRef = null;

    /**
     * @var int $productQty
     */
    private $productQty = null;

    /**
     * @var string $productAmount
     */
    private $productAmount = null;

    /**
     * @var string $productVat
     */
    private $productVat = null;

    /**
     * @var string $productExtId
     */
    private $productExtId = null;

    /**
     * @return string
     */
    public function getProductLabel()
    {
        return $this->productLabel;
    }

    /**
     * @param string $productLabel
     * @return CartItemInfo
     */
    public function setProductLabel($productLabel)
    {
        $this->productLabel = $productLabel;
        return $this;
    }

    /**
     * @return ProductType
     */
    public function getProductType()
    {
        return $this->productType;
    }

    /**
     * @param ProductType $productType
     * @return CartItemInfo
     */
    public function setProductType($productType)
    {
        $this->productType = $productType;
        return $this;
    }

    /**
     * @return string
     */
    public function getProductRef()
    {
        return $this->productRef;
    }

    /**
     * @param string $productRef
     * @return CartItemInfo
     */
    public function setProductRef($productRef)
    {
        $this->productRef = $productRef;
        return $this;
    }

    /**
     * @return int
     */
    public function getProductQty()
    {
        return $this->productQty;
    }

    /**
     * @param int $productQty
     * @return CartItemInfo
     */
    public function setProductQty($productQty)
    {
        $this->productQty = $productQty;
        return $this;
    }

    /**
     * @return string
     */
    public function getProductAmount()
    {
        return $this->productAmount;
    }

    /**
     * @param string $productAmount
     * @return CartItemInfo
     */
    public function setProductAmount($productAmount)
    {
        $this->productAmount = $productAmount;
        return $this;
    }

    /**
     * @return string
     */
    public function getProductVat()
    {
        return $this->productVat;
    }

    /**
     * @param string $productVat
     * @return CartItemInfo
     */
    public function setProductVat($productVat)
    {
        $this->productVat = $productVat;
        return $this;
    }

    /**
     * @return string
     */
    public function getProductExtId()
    {
        return $this->productExtId;
    }

    /**
     * @param string $productExtId
     * @return CartItemInfo
     */
    public function setProductExtId($productExtId)
    {
        $this->productExtId = $productExtId;
        return $this;
    }
}
