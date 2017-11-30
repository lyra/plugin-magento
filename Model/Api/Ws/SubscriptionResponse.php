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

class SubscriptionResponse
{
    /**
     * @var string $subscriptionId
     */
    private $subscriptionId = null;

    /**
     * @var \DateTime $effectDate
     */
    private $effectDate = null;

    /**
     * @var \DateTime $cancelDate
     */
    private $cancelDate = null;

    /**
     * @var int $initialAmount
     */
    private $initialAmount = null;

    /**
     * @var string $rrule
     */
    private $rrule = null;

    /**
     * @var string $description
     */
    private $description = null;

    /**
     * @var int $initialAmountNumber
     */
    private $initialAmountNumber = null;

    /**
     * @var int $pastPaymentNumber
     */
    private $pastPaymentNumber = null;

    /**
     * @var int $totalPaymentNumber
     */
    private $totalPaymentNumber = null;

    /**
     * @var int $amount
     */
    private $amount = null;

    /**
     * @var int $currency
     */
    private $currency = null;

    /**
     * @return string
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * @param string $subscriptionId
     * @return SubscriptionResponse
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEffectDate()
    {
        if ($this->effectDate == null) {
            return null;
        } else {
            try {
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->effectDate);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $effectDate
     * @return SubscriptionResponse
     */
    public function setEffectDate(\DateTime $effectDate = null)
    {
        if ($effectDate == null) {
            $this->effectDate = null;
        } else {
            $this->effectDate = $effectDate->format(\DateTime::ATOM);
        }
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCancelDate()
    {
        if ($this->cancelDate == null) {
            return null;
        } else {
            try {
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->cancelDate);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $cancelDate
     * @return SubscriptionResponse
     */
    public function setCancelDate(\DateTime $cancelDate = null)
    {
        if ($cancelDate == null) {
            $this->cancelDate = null;
        } else {
            $this->cancelDate = $cancelDate->format(\DateTime::ATOM);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getInitialAmount()
    {
        return $this->initialAmount;
    }

    /**
     * @param int $initialAmount
     * @return SubscriptionResponse
     */
    public function setInitialAmount($initialAmount)
    {
        $this->initialAmount = $initialAmount;
        return $this;
    }

    /**
     * @return string
     */
    public function getRrule()
    {
        return $this->rrule;
    }

    /**
     * @param string $rrule
     * @return SubscriptionResponse
     */
    public function setRrule($rrule)
    {
        $this->rrule = $rrule;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return SubscriptionResponse
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return int
     */
    public function getInitialAmountNumber()
    {
        return $this->initialAmountNumber;
    }

    /**
     * @param int $initialAmountNumber
     * @return SubscriptionResponse
     */
    public function setInitialAmountNumber($initialAmountNumber)
    {
        $this->initialAmountNumber = $initialAmountNumber;
        return $this;
    }

    /**
     * @return int
     */
    public function getPastPaymentNumber()
    {
        return $this->pastPaymentNumber;
    }

    /**
     * @param int $pastPaymentNumber
     * @return SubscriptionResponse
     */
    public function setPastPaymentNumber($pastPaymentNumber)
    {
        $this->pastPaymentNumber = $pastPaymentNumber;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalPaymentNumber()
    {
        return $this->totalPaymentNumber;
    }

    /**
     * @param int $totalPaymentNumber
     * @return SubscriptionResponse
     */
    public function setTotalPaymentNumber($totalPaymentNumber)
    {
        $this->totalPaymentNumber = $totalPaymentNumber;
        return $this;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return SubscriptionResponse
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param int $currency
     * @return SubscriptionResponse
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }
}
