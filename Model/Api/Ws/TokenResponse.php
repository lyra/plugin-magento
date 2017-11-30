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
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->creationDate);
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
                return \DateTime::createFromFormat(\DateTime::ATOM, $this->cancellationDate);
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
