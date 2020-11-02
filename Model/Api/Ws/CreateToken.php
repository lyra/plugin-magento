<?php
/**
 * PayZen V2-Payment Module version 2.4.11 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CreateToken
{
    /**
     * @var CommonRequest $commonRequest
     */
    private $commonRequest = null;

    /**
     * @var CardRequest $cardRequest
     */
    private $cardRequest = null;

    /**
     * @var CustomerRequest $customerRequest
     */
    private $customerRequest = null;

    /**
     * @var TokenRequest $tokenRequest
     */
    private $tokenRequest = null;

    /**
     * @return CommonRequest
     */
    public function getCommonRequest()
    {
        return $this->commonRequest;
    }

    /**
     * @param CommonRequest $commonRequest
     * @return CreateToken
     */
    public function setCommonRequest($commonRequest)
    {
        $this->commonRequest = $commonRequest;
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
     * @return CreateToken
     */
    public function setCardRequest($cardRequest)
    {
        $this->cardRequest = $cardRequest;
        return $this;
    }

    /**
     * @return CustomerRequest
     */
    public function getCustomerRequest()
    {
        return $this->customerRequest;
    }

    /**
     * @param CustomerRequest $customerRequest
     * @return CreateToken
     */
    public function setCustomerRequest($customerRequest)
    {
        $this->customerRequest = $customerRequest;
        return $this;
    }

    /**
     * @return TokenRequest
     */
    public function getTokenRequest()
    {
        return $this->tokenRequest;
    }

    /**
     * @param TokenRequest $tokenRequest
     * @return CreateToken
     */
    public function setTokenRequest($tokenRequest)
    {
        $this->tokenRequest = $tokenRequest;
        return $this;
    }
}
