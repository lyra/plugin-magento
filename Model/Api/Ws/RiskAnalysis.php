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

class RiskAnalysis
{
    /**
     * @var string $score
     */
    private $score = null;

    /**
     * @var string $resultCode
     */
    private $resultCode = null;

    /**
     * @var RiskAnalysisProcessingStatus $status
     */
    private $status = null;

    /**
     * @var string $requestId
     */
    private $requestId = null;

    /**
     * @var ExtInfo[] $extraInfo
     */
    private $extraInfo = null;

    /**
     * @var string $fingerPrintId
     */
    private $fingerPrintId = null;

    /**
     * @return string
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param string $score
     * @return RiskAnalysis
     */
    public function setScore($score)
    {
        $this->score = $score;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultCode()
    {
        return $this->resultCode;
    }

    /**
     * @param string $resultCode
     * @return RiskAnalysis
     */
    public function setResultCode($resultCode)
    {
        $this->resultCode = $resultCode;
        return $this;
    }

    /**
     * @return RiskAnalysisProcessingStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param RiskAnalysisProcessingStatus $status
     * @return RiskAnalysis
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param string $requestId
     * @return RiskAnalysis
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
        return $this;
    }

    /**
     * @return ExtInfo[]
     */
    public function getExtraInfo()
    {
        return $this->extraInfo;
    }

    /**
     * @param ExtInfo[] $extraInfo
     * @return RiskAnalysis
     */
    public function setExtraInfo(array $extraInfo = null)
    {
        $this->extraInfo = $extraInfo;
        return $this;
    }

    /**
     * @return string
     */
    public function getFingerPrintId()
    {
        return $this->fingerPrintId;
    }

    /**
     * @param string $fingerPrintId
     * @return RiskAnalysis
     */
    public function setFingerPrintId($fingerPrintId)
    {
        $this->fingerPrintId = $fingerPrintId;
        return $this;
    }
}
