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
