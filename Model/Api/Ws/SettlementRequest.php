<?php
/**
 * PayZen V2-Payment Module version 2.4.2 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class SettlementRequest
{
    /**
     * @var string[] $transactionUuids
     */
    private $transactionUuids = null;

    /**
     * @var float $commission
     */
    private $commission = null;

    /**
     * @var \DateTime $date
     */
    private $date = null;

    /**
     * @return string[]
     */
    public function getTransactionUuids()
    {
        return $this->transactionUuids;
    }

    /**
     * @param string[] $transactionUuids
     * @return SettlementRequest
     */
    public function setTransactionUuids(array $transactionUuids = null)
    {
        $this->transactionUuids = $transactionUuids;
        return $this;
    }

    /**
     * @return float
     */
    public function getCommission()
    {
        return $this->commission;
    }

    /**
     * @param float $commission
     * @return SettlementRequest
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        if ($this->date == null) {
            return null;
        } else {
            try {
                return new \DateTime($this->date);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $date
     * @return SettlementRequest
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
}
