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
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->cardHolderBirthDay);
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
}
