<?php
/**
 * PayZen V2-Payment Module version 1.11.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class TechRequest
{
    /**
     * @var string $browserUserAgent
     */
    private $browserUserAgent = null;

    /**
     * @var string $browserAccept
     */
    private $browserAccept = null;

    /**
     * @var string $integrationType
     */
    private $integrationType = null;

    /**
     * @return string
     */
    public function getBrowserUserAgent()
    {
        return $this->browserUserAgent;
    }

    /**
     * @param string $browserUserAgent
     * @return TechRequest
     */
    public function setBrowserUserAgent($browserUserAgent)
    {
        $this->browserUserAgent = $browserUserAgent;
        return $this;
    }

    /**
     * @return string
     */
    public function getBrowserAccept()
    {
        return $this->browserAccept;
    }

    /**
     * @param string $browserAccept
     * @return TechRequest
     */
    public function setBrowserAccept($browserAccept)
    {
        $this->browserAccept = $browserAccept;
        return $this;
    }

    /**
     * @return string
     */
    public function getIntegrationType()
    {
        return $this->integrationType;
    }

    /**
     * @param string $integrationType
     * @return TechRequest
     */
    public function setIntegrationType($integrationType)
    {
        $this->integrationType = $integrationType;
        return $this;
    }
}
