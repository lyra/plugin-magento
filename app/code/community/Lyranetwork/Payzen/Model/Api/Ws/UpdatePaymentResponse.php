<?php
/**
 * PayZen V2-Payment Module version 1.10.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class UpdatePaymentResponse extends WsResponse
{
    /**
     * @var UpdatePaymentResult $updatePaymentResult
     */
    private $updatePaymentResult = null;

    /**
     * @return UpdatePaymentResult
     */
    public function getUpdatePaymentResult()
    {
        return $this->updatePaymentResult;
    }

    /**
     * @param UpdatePaymentResult $updatePaymentResult
     * @return UpdatePaymentResponse
     */
    public function setUpdatePaymentResult($updatePaymentResult)
    {
        $this->updatePaymentResult = $updatePaymentResult;
        return $this;
    }
}
