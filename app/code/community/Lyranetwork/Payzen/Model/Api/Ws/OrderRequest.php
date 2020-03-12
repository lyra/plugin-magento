<?php
/**
 * PayZen V2-Payment Module version 1.11.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class OrderRequest
{
    /**
     * @var string $orderId
     */
    private $orderId = null;

    /**
     * @var ExtInfo[] $extInfo
     */
    private $extInfo = null;

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return OrderRequest
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return ExtInfo[]
     */
    public function getExtInfo()
    {
        return $this->extInfo;
    }

    /**
     * @param ExtInfo[] $extInfo
     * @return OrderRequest
     */
    public function setExtInfo(array $extInfo = null)
    {
        $this->extInfo = $extInfo;
        return $this;
    }
}
