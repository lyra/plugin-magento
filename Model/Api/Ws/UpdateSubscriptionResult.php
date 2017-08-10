<?php
/**
 * PayZen V2-Payment Module version 2.1.1 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2016 Lyra Network and contributors
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class UpdateSubscriptionResult
{
    /**
     * @var CommonResponse $commonResponse
     */
    private $commonResponse = null;

    /**
     * @var PaymentResponse $paymentResponse
     */
    private $paymentResponse = null;

    /**
     * @var OrderResponse $orderResponse
     */
    private $orderResponse = null;

    /**
     * @var CardResponse $cardResponse
     */
    private $cardResponse = null;

    /**
     * @var AuthenticationResultData $authorizationResponse
     */
    private $authorizationResponse = null;

    /**
     * @var CaptureResponse $captureResponse
     */
    private $captureResponse = null;

    /**
     * @var CustomerResponse $customerResponse
     */
    private $customerResponse = null;

    /**
     * @var MarkResponse $markResponse
     */
    private $markResponse = null;

    /**
     * @var ThreeDSResponse $threeDSResponse
     */
    private $threeDSResponse = null;

    /**
     * @var ExtraResponse $extraResponse
     */
    private $extraResponse = null;

    /**
     * @var SubscriptionResponse $subscriptionResponse
     */
    private $subscriptionResponse = null;

    /**
     * @var FraudManagementResponse $fraudManagementResponse
     */
    private $fraudManagementResponse = null;

    /**
     * @return CommonResponse
     */
    public function getCommonResponse()
    {
        return $this->commonResponse;
    }

    /**
     * @param CommonResponse $commonResponse
     * @return UpdateSubscriptionResult
     */
    public function setCommonResponse($commonResponse)
    {
        $this->commonResponse = $commonResponse;
        return $this;
    }

    /**
     * @return PaymentResponse
     */
    public function getPaymentResponse()
    {
        return $this->paymentResponse;
    }

    /**
     * @param PaymentResponse $paymentResponse
     * @return UpdateSubscriptionResult
     */
    public function setPaymentResponse($paymentResponse)
    {
        $this->paymentResponse = $paymentResponse;
        return $this;
    }

    /**
     * @return OrderResponse
     */
    public function getOrderResponse()
    {
        return $this->orderResponse;
    }

    /**
     * @param OrderResponse $orderResponse
     * @return UpdateSubscriptionResult
     */
    public function setOrderResponse($orderResponse)
    {
        $this->orderResponse = $orderResponse;
        return $this;
    }

    /**
     * @return CardResponse
     */
    public function getCardResponse()
    {
        return $this->cardResponse;
    }

    /**
     * @param CardResponse $cardResponse
     * @return UpdateSubscriptionResult
     */
    public function setCardResponse($cardResponse)
    {
        $this->cardResponse = $cardResponse;
        return $this;
    }

    /**
     * @return AuthenticationResultData
     */
    public function getAuthorizationResponse()
    {
        return $this->authorizationResponse;
    }

    /**
     * @param AuthenticationResultData $authorizationResponse
     * @return UpdateSubscriptionResult
     */
    public function setAuthorizationResponse($authorizationResponse)
    {
        $this->authorizationResponse = $authorizationResponse;
        return $this;
    }

    /**
     * @return CaptureResponse
     */
    public function getCaptureResponse()
    {
        return $this->captureResponse;
    }

    /**
     * @param CaptureResponse $captureResponse
     * @return UpdateSubscriptionResult
     */
    public function setCaptureResponse($captureResponse)
    {
        $this->captureResponse = $captureResponse;
        return $this;
    }

    /**
     * @return CustomerResponse
     */
    public function getCustomerResponse()
    {
        return $this->customerResponse;
    }

    /**
     * @param CustomerResponse $customerResponse
     * @return UpdateSubscriptionResult
     */
    public function setCustomerResponse($customerResponse)
    {
        $this->customerResponse = $customerResponse;
        return $this;
    }

    /**
     * @return markResponse
     */
    public function getMarkResponse()
    {
        return $this->markResponse;
    }

    /**
     * @param markResponse $markResponse
     * @return UpdateSubscriptionResult
     */
    public function setMarkResponse($markResponse)
    {
        $this->markResponse = $markResponse;
        return $this;
    }

    /**
     * @return ThreeDSResponse
     */
    public function getThreeDSResponse()
    {
        return $this->threeDSResponse;
    }

    /**
     * @param ThreeDSResponse $threeDSResponse
     * @return UpdateSubscriptionResult
     */
    public function setThreeDSResponse($threeDSResponse)
    {
        $this->threeDSResponse = $threeDSResponse;
        return $this;
    }

    /**
     * @return ExtraResponse
     */
    public function getExtraResponse()
    {
        return $this->extraResponse;
    }

    /**
     * @param ExtraResponse $extraResponse
     * @return UpdateSubscriptionResult
     */
    public function setExtraResponse($extraResponse)
    {
        $this->extraResponse = $extraResponse;
        return $this;
    }

    /**
     * @return SubscriptionResponse
     */
    public function getSubscriptionResponse()
    {
        return $this->subscriptionResponse;
    }

    /**
     * @param SubscriptionResponse $subscriptionResponse
     * @return UpdateSubscriptionResult
     */
    public function setSubscriptionResponse($subscriptionResponse)
    {
        $this->subscriptionResponse = $subscriptionResponse;
        return $this;
    }

    /**
     * @return fraudManagementResponse
     */
    public function getFraudManagementResponse()
    {
        return $this->fraudManagementResponse;
    }

    /**
     * @param fraudManagementResponse $fraudManagementResponse
     * @return UpdateSubscriptionResult
     */
    public function setFraudManagementResponse($fraudManagementResponse)
    {
        $this->fraudManagementResponse = $fraudManagementResponse;
        return $this;
    }
}
