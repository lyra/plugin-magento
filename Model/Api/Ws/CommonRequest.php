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
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->submissionDate);
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
