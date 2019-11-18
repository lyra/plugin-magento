<?php
/**
 * PayZen V2-Payment Module version 1.10.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CommonRequest
{
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
     * @var string $comment
     */
    private $comment = null;

    /**
     * @return string
     */
    public function getPaymentSource()
    {
        return $this->paymentSource;
    }

    /**
     * @param string $paymentSource
     * @return CommonRequest
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
                return new \DateTime($this->submissionDate);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $submissionDate
     * @return CommonRequest
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
     * @return CommonRequest
     */
    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return CommonRequest
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }
}
