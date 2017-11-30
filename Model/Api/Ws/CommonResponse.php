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

class CommonResponse
{
    /**
     * @var int $responseCode
     */
    private $responseCode = null;

    /**
     * @var string $responseCodeDetail
     */
    private $responseCodeDetail = null;

    /**
     * @var string $transactionStatusLabel
     */
    private $transactionStatusLabel = null;

    /**
     * @var string $shopId
     */
    private $shopId = null;

    /**
     * @var string $paymentSource
     */
    private $paymentSource = null;

    /**
     * @var \DateTime $submissionDate
     */
    private $submissionDate = null;

    /**
     * @var string $contractNumber
     */
    private $contractNumber = null;

    /**
     * @var string $paymentToken
     */
    private $paymentToken = null;

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @param int $responseCode
     * @return CommonResponse
     */
    public function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getResponseCodeDetail()
    {
        return $this->responseCodeDetail;
    }

    /**
     * @param string $responseCodeDetail
     * @return CommonResponse
     */
    public function setResponseCodeDetail($responseCodeDetail)
    {
        $this->responseCodeDetail = $responseCodeDetail;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionStatusLabel()
    {
        return $this->transactionStatusLabel;
    }

    /**
     * @param string $transactionStatusLabel
     * @return CommonResponse
     */
    public function setTransactionStatusLabel($transactionStatusLabel)
    {
        $this->transactionStatusLabel = $transactionStatusLabel;
        return $this;
    }

    /**
     * @return string
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @param string $shopId
     * @return CommonResponse
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentSource()
    {
        return $this->paymentSource;
    }

    /**
     * @param string $paymentSource
     * @return CommonResponse
     */
    public function setPaymentSource($paymentSource)
    {
        $this->paymentSource = $paymentSource;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSubmissionDate()
    {
        if ($this->submissionDate == null) {
            return null;
        } else {
            try {
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->submissionDate);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $submissionDate
     * @return CommonResponse
     */
    public function setSubmissionDate(\DateTime $submissionDate = null)
    {
        if ($submissionDate == null) {
            $this->submissionDate = null;
        } else {
            $this->submissionDate = $submissionDate->format(\DateTime::ATOM);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getContractNumber()
    {
        return $this->contractNumber;
    }

    /**
     * @param string $contractNumber
     * @return CommonResponse
     */
    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;
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
     * @return CommonResponse
     */
    public function setPaymentToken($paymentToken)
    {
        $this->paymentToken = $paymentToken;
        return $this;
    }
}
