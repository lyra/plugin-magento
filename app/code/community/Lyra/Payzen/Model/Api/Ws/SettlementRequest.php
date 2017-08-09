<?php
/**
 * PayZen V2-Payment Module version 1.7.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lyra\Payzen\Model\Api\Ws;

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
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->date);
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
