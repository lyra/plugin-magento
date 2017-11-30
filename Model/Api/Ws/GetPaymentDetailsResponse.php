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

class GetPaymentDetailsResponse extends WsResponse
{
    /**
     * @var GetPaymentDetailsResult $getPaymentDetailsResult
     */
    private $getPaymentDetailsResult = null;

    /**
     * @return GetPaymentDetailsResult
     */
    public function getGetPaymentDetailsResult()
    {
        return $this->getPaymentDetailsResult;
    }

    /**
     * @param GetPaymentDetailsResult $getPaymentDetailsResult
     * @return GetPaymentDetailsResponse
     */
    public function setGetPaymentDetailsResult($getPaymentDetailsResult)
    {
        $this->getPaymentDetailsResult = $getPaymentDetailsResult;
        return $this;
    }
}
