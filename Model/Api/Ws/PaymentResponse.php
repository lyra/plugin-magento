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

class PaymentResponse
{
    /**
     * @var string $transactionId
     */
    private $transactionId = null;

    /**
     * @var int $amount
     */
    private $amount = null;

    /**
     * @var int $currency
     */
    private $currency = null;

    /**
     * @var int $effectiveAmount
     */
    private $effectiveAmount = null;

    /**
     * @var int $effectiveCurrency
     */
    private $effectiveCurrency = null;

    /**
     * @var \DateTime $expectedCaptureDate
     */
    private $expectedCaptureDate = null;

    /**
     * @var int $manualValidation
     */
    private $manualValidation = null;

    /**
     * @var int $operationType
     */
    private $operationType = null;

    /**
     * @var \DateTime $creationDate
     */
    private $creationDate = null;

    /**
     * @var string $externalTransactionId
     */
    private $externalTransactionId = null;

    /**
     * @var string $liabilityShift
     */
    private $liabilityShift = null;

    /**
     * @var string $transactionUuid
     */
    private $transactionUuid = null;

    /**
     * @var int $sequenceNumber
     */
    private $sequenceNumber = null;

    /**
     * @var PaymentType $paymentType
     */
    private $paymentType = null;

    /**
     * @var int $paymentError
     */
    private $paymentError = null;

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     * @return PaymentResponse
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
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
     * @return PaymentResponse
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
     * @return PaymentResponse
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return int
     */
    public function getEffectiveAmount()
    {
        return $this->effectiveAmount;
    }

    /**
     * @param int $effectiveAmount
     * @return PaymentResponse
     */
    public function setEffectiveAmount($effectiveAmount)
    {
        $this->effectiveAmount = $effectiveAmount;
        return $this;
    }

    /**
     * @return int
     */
    public function getEffectiveCurrency()
    {
        return $this->effectiveCurrency;
    }

    /**
     * @param int $effectiveCurrency
     * @return PaymentResponse
     */
    public function setEffectiveCurrency($effectiveCurrency)
    {
        $this->effectiveCurrency = $effectiveCurrency;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpectedCaptureDate()
    {
        if ($this->expectedCaptureDate == null) {
            return null;
        } else {
            try {
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->expectedCaptureDate);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $expectedCaptureDate
     * @return PaymentResponse
     */
    public function setExpectedCaptureDate(\DateTime $expectedCaptureDate = null)
    {
        if ($expectedCaptureDate == null) {
            $this->expectedCaptureDate = null;
        } else {
            $this->expectedCaptureDate = $expectedCaptureDate->format(\DateTime::ATOM);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getManualValidation()
    {
        return $this->manualValidation;
    }

    /**
     * @param int $manualValidation
     * @return PaymentResponse
     */
    public function setManualValidation($manualValidation)
    {
        $this->manualValidation = $manualValidation;
        return $this;
    }

    /**
     * @return int
     */
    public function getOperationType()
    {
        return $this->operationType;
    }

    /**
     * @param int $operationType
     * @return PaymentResponse
     */
    public function setOperationType($operationType)
    {
        $this->operationType = $operationType;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        if ($this->creationDate == null) {
            return null;
        } else {
            try {
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->creationDate);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $creationDate
     * @return PaymentResponse
     */
    public function setCreationDate(\DateTime $creationDate = null)
    {
        if ($creationDate == null) {
            $this->creationDate = null;
        } else {
            $this->creationDate = $creationDate->format(\DateTime::ATOM);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getExternalTransactionId()
    {
        return $this->externalTransactionId;
    }

    /**
     * @param string $externalTransactionId
     * @return PaymentResponse
     */
    public function setExternalTransactionId($externalTransactionId)
    {
        $this->externalTransactionId = $externalTransactionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getLiabilityShift()
    {
        return $this->liabilityShift;
    }

    /**
     * @param string $liabilityShift
     * @return PaymentResponse
     */
    public function setLiabilityShift($liabilityShift)
    {
        $this->liabilityShift = $liabilityShift;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionUuid()
    {
        return $this->transactionUuid;
    }

    /**
     * @param string $transactionUuid
     * @return PaymentResponse
     */
    public function setTransactionUuid($transactionUuid)
    {
        $this->transactionUuid = $transactionUuid;
        return $this;
    }

    /**
     * @return int
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    /**
     * @param int $sequenceNumber
     * @return PaymentResponse
     */
    public function setSequenceNumber($sequenceNumber)
    {
        $this->sequenceNumber = $sequenceNumber;
        return $this;
    }

    /**
     * @return PaymentType
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @param PaymentType $paymentType
     * @return PaymentResponse
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
        return $this;
    }

    /**
     * @return int
     */
    public function getPaymentError()
    {
        return $this->paymentError;
    }

    /**
     * @param int $paymentError
     * @return PaymentResponse
     */
    public function setPaymentError($paymentError)
    {
        $this->paymentError = $paymentError;
        return $this;
    }
}
