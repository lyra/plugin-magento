<?php
/**
 * PayZen V2-Payment Module version 2.4.1 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class PaymentRequest
{
    /**
     * @var string $transactionId
     */
    private $transactionId = null;

    /**
     * @var string $retryUuid
     */
    private $retryUuid = null;

    /**
     * @var int $amount
     */
    private $amount = null;

    /**
     * @var int $currency
     */
    private $currency = null;

    /**
     * @var \DateTime $expectedCaptureDate
     */
    private $expectedCaptureDate = null;

    /**
     * @var int $manualValidation
     */
    private $manualValidation = null;

    /**
     * @var string $paymentOptionCode
     */
    private $paymentOptionCode = null;

    /**
     * @var string $acquirerTransientData
     */
    private $acquirerTransientData = null;

    /**
     * @var int $firstInstallmentDelay
     */
    private $firstInstallmentDelay = null;

    /**
     * @var string $overridePaymentCinematic
     */
    private $overridePaymentCinematic = null;

    /**
     * @var string $taxRate
     */
    private $taxRate = null;

    /**
     * @var int $taxAmount
     */
    private $taxAmount = null;

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     * @return PaymentRequest
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getRetryUuid()
    {
        return $this->retryUuid;
    }

    /**
     * @param string $retryUuid
     * @return PaymentRequest
     */
    public function setRetryUuid($retryUuid)
    {
        $this->retryUuid = $retryUuid;
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
     * @return PaymentRequest
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
     * @return PaymentRequest
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
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
                return new \DateTime($this->expectedCaptureDate);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $expectedCaptureDate
     * @return PaymentRequest
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
     * @return PaymentRequest
     */
    public function setManualValidation($manualValidation)
    {
        $this->manualValidation = $manualValidation;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentOptionCode()
    {
        return $this->paymentOptionCode;
    }

    /**
     * @param string $paymentOptionCode
     * @return PaymentRequest
     */
    public function setPaymentOptionCode($paymentOptionCode)
    {
        $this->paymentOptionCode = $paymentOptionCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getAcquirerTransientData()
    {
        return $this->acquirerTransientData;
    }

    /**
     * @param string $acquirerTransientData
     * @return PaymentRequest
     */
    public function setAcquirerTransientData($acquirerTransientData)
    {
        $this->acquirerTransientData = $acquirerTransientData;
        return $this;
    }

    /**
     * @return int
     */
    public function getFirstInstallmentDelay()
    {
        return $this->firstInstallmentDelay;
    }

    /**
     * @param int $firstInstallmentDelay
     * @return PaymentRequest
     */
    public function setFirstInstallmentDelay($firstInstallmentDelay)
    {
        $this->firstInstallmentDelay = $firstInstallmentDelay;
        return $this;
    }

    /**
     * @return string
     */
    public function getOverridePaymentCinematic()
    {
        return $this->overridePaymentCinematic;
    }

    /**
     * @param string $overridePaymentCinematic
     * @return PaymentRequest
     */
    public function setOverridePaymentCinematic($overridePaymentCinematic)
    {
        $this->overridePaymentCinematic = $overridePaymentCinematic;
        return $this;
    }

    /**
     * @return string
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * @param string $taxRate
     * @return PaymentRequest
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;
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
     * @return PaymentRequest
     */
    public function setTaxAmount($taxAmount)
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }
}
