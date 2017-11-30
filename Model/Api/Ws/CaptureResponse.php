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

class CaptureResponse
{
    /**
     * @var \DateTime $date
     */
    private $date = null;

    /**
     * @var int $number
     */
    private $number = null;

    /**
     * @var int $reconciliationStatus
     */
    private $reconciliationStatus = null;

    /**
     * @var int $refundAmount
     */
    private $refundAmount = null;

    /**
     * @var int $refundCurrency
     */
    private $refundCurrency = null;

    /**
     * @var boolean $chargeback
     */
    private $chargeback = null;

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        if ($this->date == null) {
            return null;
        } else {
            try {
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->date);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $date
     * @return CaptureResponse
     */
    public function setDate(\DateTime $date = null)
    {
        if ($date == null) {
            $this->date = null;
        } else {
            $this->date = $date->format(\DateTime::ATOM);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     * @return CaptureResponse
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return int
     */
    public function getReconciliationStatus()
    {
        return $this->reconciliationStatus;
    }

    /**
     * @param int $reconciliationStatus
     * @return CaptureResponse
     */
    public function setReconciliationStatus($reconciliationStatus)
    {
        $this->reconciliationStatus = $reconciliationStatus;
        return $this;
    }

    /**
     * @return int
     */
    public function getRefundAmount()
    {
        return $this->refundAmount;
    }

    /**
     * @param int $refundAmount
     * @return CaptureResponse
     */
    public function setRefundAmount($refundAmount)
    {
        $this->refundAmount = $refundAmount;
        return $this;
    }

    /**
     * @return int
     */
    public function getRefundCurrency()
    {
        return $this->refundCurrency;
    }

    /**
     * @param int $refundCurrency
     * @return CaptureResponse
     */
    public function setRefundCurrency($refundCurrency)
    {
        $this->refundCurrency = $refundCurrency;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getChargeback()
    {
        return $this->chargeback;
    }

    /**
     * @param boolean $chargeback
     * @return CaptureResponse
     */
    public function setChargeback($chargeback)
    {
        $this->chargeback = $chargeback;
        return $this;
    }
}
