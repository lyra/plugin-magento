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

class FraudManagementResponse
{
    /**
     * @var RiskControl[] $riskControl
     */
    private $riskControl = null;

    /**
     * @var RiskAnalysis[] $riskAnalysis
     */
    private $riskAnalysis = null;

    /**
     * @var RiskAssessments $riskAssessments
     */
    private $riskAssessments = null;

    /**
     * @return RiskControl[]
     */
    public function getRiskControl()
    {
        return $this->riskControl;
    }

    /**
     * @param RiskControl[] $riskControl
     * @return FraudManagementResponse
     */
    public function setRiskControl(array $riskControl = null)
    {
        $this->riskControl = $riskControl;
        return $this;
    }

    /**
     * @return RiskAnalysis[]
     */
    public function getRiskAnalysis()
    {
        return $this->riskAnalysis;
    }

    /**
     * @param RiskAnalysis[] $riskAnalysis
     * @return FraudManagementResponse
     */
    public function setRiskAnalysis(array $riskAnalysis = null)
    {
        $this->riskAnalysis = $riskAnalysis;
        return $this;
    }

    /**
     * @return RiskAssessments
     */
    public function getRiskAssessments()
    {
        return $this->riskAssessments;
    }

    /**
     * @param RiskAssessments $riskAssessments
     * @return FraudManagementResponse
     */
    public function setRiskAssessments($riskAssessments)
    {
        $this->riskAssessments = $riskAssessments;
        return $this;
    }
}
