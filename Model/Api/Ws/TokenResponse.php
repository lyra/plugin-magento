<?php
/**
 * PayZen V2-Payment Module version 2.4.2 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class TokenResponse
{
    /**
     * @var \DateTime $creationDate
     */
    private $creationDate = null;

    /**
     * @var \DateTime $cancellationDate
     */
    private $cancellationDate = null;

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        if ($this->creationDate == null) {
            return null;
        } else {
            try {
                return new \DateTime($this->creationDate);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $creationDate
     * @return TokenResponse
     */
    public function setCreationDate(\DateTime $creationDate = null)
    {
        if ($creationDate == null) {
            $this->creationDate = null;
        } else {
            $this->creationDate = $creationDate->format(\DateTime::ATOM);
        }
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCancellationDate()
    {
        if ($this->cancellationDate == null) {
            return null;
        } else {
            try {
                return new \DateTime($this->cancellationDate);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $cancellationDate
     * @return TokenResponse
     */
    public function setCancellationDate(\DateTime $cancellationDate = null)
    {
        if ($cancellationDate == null) {
            $this->cancellationDate = null;
        } else {
            $this->cancellationDate = $cancellationDate->format(\DateTime::ATOM);
        }
        return $this;
    }
}
