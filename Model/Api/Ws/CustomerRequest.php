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

class CustomerRequest
{
    /**
     * @var BillingDetailsRequest $billingDetails
     */
    private $billingDetails = null;

    /**
     * @var ShippingDetailsRequest $shippingDetails
     */
    private $shippingDetails = null;

    /**
     * @var ExtraDetailsRequest $extraDetails
     */
    private $extraDetails = null;

    /**
     * @return BillingDetailsRequest
     */
    public function getBillingDetails()
    {
        return $this->billingDetails;
    }

    /**
     * @param BillingDetailsRequest $billingDetails
     * @return CustomerRequest
     */
    public function setBillingDetails($billingDetails)
    {
        $this->billingDetails = $billingDetails;
        return $this;
    }

    /**
     * @return ShippingDetailsRequest
     */
    public function getShippingDetails()
    {
        return $this->shippingDetails;
    }

    /**
     * @param ShippingDetailsRequest $shippingDetails
     * @return CustomerRequest
     */
    public function setShippingDetails($shippingDetails)
    {
        $this->shippingDetails = $shippingDetails;
        return $this;
    }

    /**
     * @return ExtraDetailsRequest
     */
    public function getExtraDetails()
    {
        return $this->extraDetails;
    }

    /**
     * @param ExtraDetailsRequest $extraDetails
     * @return CustomerRequest
     */
    public function setExtraDetails($extraDetails)
    {
        $this->extraDetails = $extraDetails;
        return $this;
    }
}
