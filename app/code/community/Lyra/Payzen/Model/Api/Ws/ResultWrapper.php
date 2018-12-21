<?php
/**
 * PayZen V2-Payment Module version 1.9.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Class wrapper for WS result received from PayZen Web Services.
 */
class Lyra_Payzen_Model_Api_Ws_ResultWrapper
{
    protected $response = array();

    public function __construct(
        $commonResponse,
        $paymentResponse = null,
        $authorizationResponse = null,
        $cardResponse = null,
        $threeDSResponse = null,
        $fraudManagementResponse = null
    ) {
        $this->response['result'] = sprintf('%02d', $commonResponse->getResponseCode());
        $this->response['extra_result'] = ''; // it is directly returned in responseCodeDetail
        $this->response['message'] = $commonResponse->getResponseCodeDetail();
        $this->response['trans_status'] = $commonResponse->getTransactionStatusLabel();

        if ($paymentResponse) {
            $this->response['warranty_result'] = $paymentResponse->getLiabilityShift();
            $this->response['trans_id'] = $paymentResponse->getTransactionId();
            $this->response['trans_uuid'] = $paymentResponse->getTransactionUuid();
            $this->response['sequence_number'] = $paymentResponse->getSequenceNumber();
            $this->response['operation_type'] = $paymentResponse->getOperationType() == 1 ? 'CREDIT' : 'DEBIT';

            $this->response['currency'] = $paymentResponse->getCurrency();
            $this->response['amount'] = $paymentResponse->getAmount();
            $this->response['effective_amount'] = $paymentResponse->getEffectiveAmount();

            $date = $paymentResponse->getExpectedCaptureDate() ?
                $paymentResponse->getExpectedCaptureDate()->getTimestamp() : time();
            $this->response['presentation_date'] = date('YmdHis', $date);
        }

        if ($authorizationResponse) {
            $this->response['auth_result'] = sprintf('%02d', $authorizationResponse->getResult());
        }

        if ($cardResponse) {
            $this->response['card_brand'] = $cardResponse->getBrand();
            $this->response['card_number'] = $cardResponse->getNumber();
            $this->response['expiry_month'] = $cardResponse->getExpiryMonth();
            $this->response['expiry_year'] = $cardResponse->getExpiryYear();
        }

        if ($threeDSResponse) {
            $this->response['threeds_status'] = $threeDSResponse->getAuthenticationResultData()->getStatus();
            $this->response['threeds_cavv'] = $threeDSResponse->getAuthenticationResultData()->getCavv();
        }

        if ($fraudManagementResponse) {
            $this->response['risk_control'] = $fraudManagementResponse->getRiskControl();
            $this->response['risk_assessment_result'] = $fraudManagementResponse->getRiskAssessments() ?
                $fraudManagementResponse->getRiskAssessments()->getResults() : '';
        }
    }

    /**
     * Check if the payment was successful (authorized or captured).
     *
     * @return boolean
     */
    public function isAcceptedPayment()
    {
        $confirmedStatuses = array('AUTHORISED', 'AUTHORISED_TO_VALIDATE', 'CAPTURED', 'CAPTURE_FAILED');
        return in_array($this->getTransStatus(), $confirmedStatuses) || $this->isPendingPayment();
    }

    /**
     * Check if the payment is waiting confirmation (successful but the amount has not
     *  been transfered and is not yet guaranteed).
     *
     * @return boolean
     */
    public function isPendingPayment()
    {
        $pendingStatuses = array(
            'INITIAL',
            'WAITING_AUTHORISATION',
            'WAITING_AUTHORISATION_TO_VALIDATE',
            'UNDER_VERIFICATION'
        );
        return in_array($this->getTransStatus(), $pendingStatuses);
    }

    /**
     * Check if the payment process was interrupted by the client.
     *
     * @return bool
     */
    public function isCancelledPayment()
    {
        $cancelledStatuses = array('NOT_CREATED', 'ABANDONED');
        return in_array($this->getTransStatus(), $cancelledStatuses);
    }

    /**
     * Check if the payment is to validate manually in the PayZen Back Office.
     *
     * @return boolean
     */
    public function isToValidatePayment()
    {
        $toValidateStatuses = array('WAITING_AUTHORISATION_TO_VALIDATE', 'AUTHORISED_TO_VALIDATE');
        return in_array($this->getTransStatus(), $toValidateStatuses);
    }

    /**
     * Check if the payment is suspected to be fraudulent.
     *
     * @return boolean
     */
    public function isSuspectedFraud()
    {
        $riskControl = $this->getRiskControl();

        // at least one control failed
        return in_array('WARNING', $riskControl) || in_array('ERROR', $riskControl);
    }

    /**
     * Return the risk control result.
     *
     * @return boolean
     */
    public function getRiskControl()
    {
        $controls = $this->get('risk_control');
        if (! is_array($controls)) {
            return array();
        }

        $results = array();
        foreach ($controls as $control) {
            $results[$control->getName()] = $control->getResult();
        }
        return $results;
    }

    /**
     * Return the risk assessment result.
     *
     * @return array[string]
     */
    public function getRiskAssessment()
    {
        $riskAssessment = $this->get('risk_assessment_result');
        if (! isset($riskAssessment) || ! trim($riskAssessment)) {
            return array();
        }

        return explode(';', $riskAssessment);
    }

    /**
     * Return the value of a response parameter.
     *
     * @param  string $name
     * @return string
     */
    public function get($key)
    {
        return @$this->response[$key];
    }

    /**
     * Return the payment response result.
     *
     * @return string
     */
    public function getResult()
    {
        return $this->get('result');
    }

    /**
     * Return all the payment response results as array.
     *
     * @return array[string][string]
     */
    public function getAllResults()
    {
        return array(
                'result' => $this->get('result'),
                'extra_result' => $this->get('extra_result'),
                'auth_result' => $this->get('auth_result'),
                'warranty_result' => $this->get('warranty_result')
        );
    }

    /**
     * Return the payment transaction status.
     *
     * @return string
     */
    public function getTransStatus()
    {
        return $this->get('trans_status');
    }

    /**
     * Return the payment response message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->get('message') . '.';
    }
}
