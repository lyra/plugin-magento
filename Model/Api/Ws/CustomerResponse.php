<?php
/**
 * PayZen V2-Payment Module version 2.4.1 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
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
