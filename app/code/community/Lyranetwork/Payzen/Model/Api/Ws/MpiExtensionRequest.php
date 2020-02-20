<?php
/**
 * PayZen V2-Payment Module version 1.10.3 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class MpiExtensionRequest
{
    /**
     * @var ExtInfo[] $extensionData
     */
    private $extensionData = null;

    /**
     * @return ExtInfo[]
     */
    public function getExtensionData()
    {
        return $this->extensionData;
    }

    /**
     * @param ExtInfo[] $extensionData
     * @return MpiExtensionRequest
     */
    public function setExtensionData(array $extensionData = null)
    {
        $this->extensionData = $extensionData;
        return $this;
    }
}
