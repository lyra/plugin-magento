<?php
/**
 * PayZen V2-Payment Module version 2.4.5 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CardRequest
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
     * @var int $expiryMonth
     */
    private $expiryMonth = null;

    /**
     * @var int $expiryYear
     */
    private $expiryYear = null;

    /**
     * @var string $cardSecurityCode
     */
    private $cardSecurityCode = null;

    /**
     * @var \DateTime $cardHolderBirthDay
     */
    private $cardHolderBirthDay = null;

    /**
     * @var string $paymentToken
     */
    private $paymentToken = null;

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
     * @var string $walletPayload
     */
    private $walletPayload = null;

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return CardRequest
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
     * @return CardRequest
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
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
     * @return CardRequest
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
     * @return CardRequest
     */
    public function setExpiryYear($expiryYear)
    {
        $this->expiryYear = $expiryYear;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardSecurityCode()
    {
        return $this->cardSecurityCode;
    }

    /**
     * @param string $cardSecurityCode
     * @return CardRequest
     */
    public function setCardSecurityCode($cardSecurityCode)
    {
        $this->cardSecurityCode = $cardSecurityCode;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCardHolderBirthDay()
    {
        if ($this->cardHolderBirthDay == null) {
            return null;
        } else {
            try {
                return new \DateTime($this->cardHolderBirthDay);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $cardHolderBirthDay
     * @return CardRequest
     */
    public function setCardHolderBirthDay(\DateTime $cardHolderBirthDay = null)
    {
        if ($cardHolderBirthDay == null) {
            $this->cardHolderBirthDay = null;
        } else {
            $this->cardHolderBirthDay = $cardHolderBirthDay->format(\DateTime::ATOM);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentToken()
    {
        return $this->paymentToken;
    }

    /**
     * @param string $paymentToken
     * @return CardRequest
     */
    public function setPaymentToken($paymentToken)
    {
        $this->paymentToken = $paymentToken;
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
     * @return CardRequest
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
     * @return CardRequest
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
     * @return CardRequest
     */
    public function setProofOfIdNumber($proofOfIdNumber)
    {
        $this->proofOfIdNumber = $proofOfIdNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getWalletPayload()
    {
        return $this->walletPayload;
    }

    /**
     * @param string $walletPayload
     * @return CardRequest
     */
    public function setWalletPayload($walletPayload)
    {
        $this->walletPayload = $walletPayload;
        return $this;
    }
}
