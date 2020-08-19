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

class ExtraResponse
{
    /**
     * @var string $paymentOptionCode
     */
    private $paymentOptionCode = null;

    /**
     * @var int $paymentOptionOccNumber
     */
    private $paymentOptionOccNumber = null;

    /**
     * @var string $boletoPdfUrl
     */
    private $boletoPdfUrl = null;

    /**
     * @return string
     */
    public function getPaymentOptionCode()
    {
        return $this->paymentOptionCode;
    }

    /**
     * @param string $paymentOptionCode
     * @return ExtraResponse
     */
    public function setPaymentOptionCode($paymentOptionCode)
    {
        $this->paymentOptionCode = $paymentOptionCode;
        return $this;
    }

    /**
     * @return int
     */
    public function getPaymentOptionOccNumber()
    {
        return $this->paymentOptionOccNumber;
    }

    /**
     * @param int $paymentOptionOccNumber
     * @return ExtraResponse
     */
    public function setPaymentOptionOccNumber($paymentOptionOccNumber)
    {
        $this->paymentOptionOccNumber = $paymentOptionOccNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getBoletoPdfUrl()
    {
        return $this->boletoPdfUrl;
    }

    /**
     * @param string $boletoPdfUrl
     * @return ExtraResponse
     */
    public function setBoletoPdfUrl($boletoPdfUrl)
    {
        $this->boletoPdfUrl = $boletoPdfUrl;
        return $this;
    }
}
