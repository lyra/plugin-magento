<?php
/**
 * PayZen V2-Payment Module version 1.11.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CreateTokenByIban
{
    /**
     * @var CommonRequest $commonRequest
     */
    private $commonRequest = null;

    /**
     * @var IbanRequest $ibanRequest
     */
    private $ibanRequest = null;

    /**
     * @var CustomerRequest $customerRequest
     */
    private $customerRequest = null;

    /**
     * @return CommonRequest
     */
    public function getCommonRequest()
    {
        return $this->commonRequest;
    }

    /**
     * @param CommonRequest $commonRequest
     * @return CreateTokenByIban
     */
    public function setCommonRequest($commonRequest)
    {
        $this->commonRequest = $commonRequest;
        return $this;
    }

    /**
     * @return IbanRequest
     */
    public function getIbanRequest()
    {
        return $this->ibanRequest;
    }

    /**
     * @param IbanRequest $ibanRequest
     * @return CreateTokenByIban
     */
    public function setIbanRequest($ibanRequest)
    {
        $this->ibanRequest = $ibanRequest;
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
     * @return CreateTokenByIban
     */
    public function setCustomerRequest($customerRequest)
    {
        $this->customerRequest = $customerRequest;
        return $this;
    }
}
