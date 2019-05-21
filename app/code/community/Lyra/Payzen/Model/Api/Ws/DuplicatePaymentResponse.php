<?php
/**
 * PayZen V2-Payment Module version 1.9.4 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyra\Payzen\Model\Api\Ws;

class DuplicatePaymentResponse extends WsResponse
{
    /**
     * @var DuplicatePaymentResult $duplicatePaymentResult
     */
    private $duplicatePaymentResult = null;

    /**
     * @return DuplicatePaymentResult
     */
    public function getDuplicatePaymentResult()
    {
        return $this->duplicatePaymentResult;
    }

    /**
     * @param DuplicatePaymentResult $duplicatePaymentResult
     * @return DuplicatePaymentResponse
     */
    public function setDuplicatePaymentResult($duplicatePaymentResult)
    {
        $this->duplicatePaymentResult = $duplicatePaymentResult;
        return $this;
    }
}
