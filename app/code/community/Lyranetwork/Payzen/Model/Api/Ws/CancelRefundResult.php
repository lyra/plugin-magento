<?php
/**
 * PayZen V2-Payment Module version 1.11.4 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CancelRefundResult
{
    /**
     * @var CommonResponse $commonResponse
     */
    private $commonResponse = null;

    /**
     * @return CommonResponse
     */
    public function getCommonResponse()
    {
        return $this->commonResponse;
    }

    /**
     * @param CommonResponse $commonResponse
     * @return CancelRefundResult
     */
    public function setCommonResponse($commonResponse)
    {
        $this->commonResponse = $commonResponse;
        return $this;
    }
}
