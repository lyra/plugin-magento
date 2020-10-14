<?php
/**
 * PayZen V2-Payment Module version 1.11.3 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class UpdateRefundResponse extends WsResponse
{
    /**
     * @var UpdateRefundResult $updateRefundResult
     */
    private $updateRefundResult = null;

    /**
     * @return UpdateRefundResult
     */
    public function getUpdateRefundResult()
    {
        return $this->updateRefundResult;
    }

    /**
     * @param UpdateRefundResult $updateRefundResult
     * @return UpdateRefundResponse
     */
    public function setUpdateRefundResult($updateRefundResult)
    {
        $this->updateRefundResult = $updateRefundResult;
        return $this;
    }
}
