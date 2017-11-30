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

class OrderResponse
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
     * @return OrderResponse
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
     * @return OrderResponse
     */
    public function setExtInfo(array $extInfo = null)
    {
        $this->extInfo = $extInfo;
        return $this;
    }
}
