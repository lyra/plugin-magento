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

class CustomerResponse
{
    /**
     * @var BillingDetailsResponse $billingDetails
     */
    private $billingDetails = null;

    /**
     * @var ShippingDetailsResponse $shippingDetails
     */
    private $shippingDetails = null;

    /**
     * @var ExtraDetailsResponse $extraDetails
     */
    private $extraDetails = null;

    /**
     * @return BillingDetailsResponse
     */
    public function getBillingDetails()
    {
        return $this->billingDetails;
    }

    /**
     * @param BillingDetailsResponse $billingDetails
     * @return CustomerResponse
     */
    public function setBillingDetails($billingDetails)
    {
        $this->billingDetails = $billingDetails;
        return $this;
    }

    /**
     * @return ShippingDetailsResponse
     */
    public function getShippingDetails()
    {
        return $this->shippingDetails;
    }

    /**
     * @param ShippingDetailsResponse $shippingDetails
     * @return CustomerResponse
     */
    public function setShippingDetails($shippingDetails)
    {
        $this->shippingDetails = $shippingDetails;
        return $this;
    }

    /**
     * @return ExtraDetailsResponse
     */
    public function getExtraDetails()
    {
        return $this->extraDetails;
    }

    /**
     * @param ExtraDetailsResponse $extraDetails
     * @return CustomerResponse
     */
    public function setExtraDetails($extraDetails)
    {
        $this->extraDetails = $extraDetails;
        return $this;
    }
}
