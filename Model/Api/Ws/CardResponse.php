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

class CardResponse
{
    /**
     * @var string $number
     */
    private $number = null;

    /**
     * @var string $scheme
     */
    private $scheme = null;

    /**
     * @var string $brand
     */
    private $brand = null;

    /**
     * @var string $country
     */
    private $country = null;

    /**
     * @var string $productCode
     */
    private $productCode = null;

    /**
     * @var string $bankCode
     */
    private $bankCode = null;

    /**
     * @var string $bankLabel
     */
    private $bankLabel = null;

    /**
     * @var int $expiryMonth
     */
    private $expiryMonth = null;

    /**
     * @var int $expiryYear
     */
    private $expiryYear = null;

    /**
     * @var string $cardHolderName
     */
    private $cardHolderName = null;

    /**
     * @var string $proofOfIdType
     */
    private $proofOfIdType = null;

    /**
     * @var string $proofOfIdNumber
     */
    private $proofOfIdNumber = null;

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return CardResponse
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     * @return CardResponse
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     * @return CardResponse
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return CardResponse
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getProductCode()
    {
        return $this->productCode;
    }

    /**
     * @param string $productCode
     * @return CardResponse
     */
    public function setProductCode($productCode)
    {
        $this->productCode = $productCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getBankCode()
    {
        return $this->bankCode;
    }

    /**
     * @param string $bankCode
     * @return CardResponse
     */
    public function setBankCode($bankCode)
    {
        $this->bankCode = $bankCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getBankLabel()
    {
        return $this->bankLabel;
    }

    /**
     * @param string $bankLabel
     * @return CardResponse
     */
    public function setBankLabel($bankLabel)
    {
        $this->bankLabel = $bankLabel;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpiryMonth()
    {
        return $this->expiryMonth;
    }

    /**
     * @param int $expiryMonth
     * @return CardResponse
     */
    public function setExpiryMonth($expiryMonth)
    {
        $this->expiryMonth = $expiryMonth;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpiryYear()
    {
        return $this->expiryYear;
    }

    /**
     * @param int $expiryYear
     * @return CardResponse
     */
    public function setExpiryYear($expiryYear)
    {
        $this->expiryYear = $expiryYear;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardHolderName()
    {
        return $this->cardHolderName;
    }

    /**
     * @param string $cardHolderName
     * @return CardResponse
     */
    public function setCardHolderName($cardHolderName)
    {
        $this->cardHolderName = $cardHolderName;
        return $this;
    }

    /**
     * @return string
     */
    public function getProofOfIdType()
    {
        return $this->proofOfIdType;
    }

    /**
     * @param string $proofOfIdType
     * @return CardResponse
     */
    public function setProofOfIdType($proofOfIdType)
    {
        $this->proofOfIdType = $proofOfIdType;
        return $this;
    }

    /**
     * @return string
     */
    public function getProofOfIdNumber()
    {
        return $this->proofOfIdNumber;
    }

    /**
     * @param string $proofOfIdNumber
     * @return CardResponse
     */
    public function setProofOfIdNumber($proofOfIdNumber)
    {
        $this->proofOfIdNumber = $proofOfIdNumber;
        return $this;
    }
}
