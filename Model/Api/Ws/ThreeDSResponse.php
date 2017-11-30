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

class ThreeDSResponse
{
    /**
     * @var AuthenticationRequestData $authenticationRequestData
     */
    private $authenticationRequestData = null;

    /**
     * @var AuthenticationResultData $authenticationResultData
     */
    private $authenticationResultData = null;

    /**
     * @return AuthenticationRequestData
     */
    public function getAuthenticationRequestData()
    {
        return $this->authenticationRequestData;
    }

    /**
     * @param AuthenticationRequestData $authenticationRequestData
     * @return ThreeDSResponse
     */
    public function setAuthenticationRequestData($authenticationRequestData)
    {
        $this->authenticationRequestData = $authenticationRequestData;
        return $this;
    }

    /**
     * @return AuthenticationResultData
     */
    public function getAuthenticationResultData()
    {
        return $this->authenticationResultData;
    }

    /**
     * @param AuthenticationResultData $authenticationResultData
     * @return ThreeDSResponse
     */
    public function setAuthenticationResultData($authenticationResultData)
    {
        $this->authenticationResultData = $authenticationResultData;
        return $this;
    }
}
