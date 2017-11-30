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

class VerifyThreeDSEnrollment
{
    /**
     * @var CommonRequest $commonRequest
     */
    private $commonRequest = null;

    /**
     * @var PaymentRequest $paymentRequest
     */
    private $paymentRequest = null;

    /**
     * @var CardRequest $cardRequest
     */
    private $cardRequest = null;

    /**
     * @var TechRequest $techRequest
     */
    private $techRequest = null;

    /**
     * @var ThreeDSRequest $threeDSRequest
     */
    private $threeDSRequest = null;

    /**
     * @return CommonRequest
     */
    public function getCommonRequest()
    {
        return $this->commonRequest;
    }

    /**
     * @param CommonRequest $commonRequest
     * @return VerifyThreeDSEnrollment
     */
    public function setCommonRequest($commonRequest)
    {
        $this->commonRequest = $commonRequest;
        return $this;
    }

    /**
     * @return PaymentRequest
     */
    public function getPaymentRequest()
    {
        return $this->paymentRequest;
    }

    /**
     * @param PaymentRequest $paymentRequest
     * @return VerifyThreeDSEnrollment
     */
    public function setPaymentRequest($paymentRequest)
    {
        $this->paymentRequest = $paymentRequest;
        return $this;
    }

    /**
     * @return CardRequest
     */
    public function getCardRequest()
    {
        return $this->cardRequest;
    }

    /**
     * @param CardRequest $cardRequest
     * @return VerifyThreeDSEnrollment
     */
    public function setCardRequest($cardRequest)
    {
        $this->cardRequest = $cardRequest;
        return $this;
    }

    /**
     * @return TechRequest
     */
    public function getTechRequest()
    {
        return $this->techRequest;
    }

    /**
     * @param TechRequest $techRequest
     * @return VerifyThreeDSEnrollment
     */
    public function setTechRequest($techRequest)
    {
        $this->techRequest = $techRequest;
        return $this;
    }

    /**
     * @return ThreeDSRequest
     */
    public function getThreeDSRequest()
    {
        return $this->threeDSRequest;
    }

    /**
     * @param ThreeDSRequest $threeDSRequest
     * @return VerifyThreeDSEnrollment
     */
    public function setThreeDSRequest($threeDSRequest)
    {
        $this->threeDSRequest = $threeDSRequest;
        return $this;
    }
}
