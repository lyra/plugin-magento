<?php
/**
 * PayZen V2-Payment Module version 2.4.6 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class CreateTokenByIbanResponse extends WsResponse
{
    /**
     * @var CreateTokenByIbanResult $createTokenByIbanResult
     */
    private $createTokenByIbanResult = null;

    /**
     * @return CreateTokenByIbanResult
     */
    public function getCreateTokenByIbanResult()
    {
        return $this->createTokenByIbanResult;
    }

    /**
     * @param CreateTokenByIbanResult $createTokenByIbanResult
     * @return CreateTokenByIbanResponse
     */
    public function setCreateTokenByIbanResult($createTokenByIbanResult)
    {
        $this->createTokenByIbanResult = $createTokenByIbanResult;
        return $this;
    }
}
