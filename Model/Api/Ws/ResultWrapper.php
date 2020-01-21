<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Class wrapper for WS result received from gateway Web Services.
 */
namespace Lyranetwork\Payzen\Model\Api\Ws;

class ResultWrapper
{
    protected $response = [];

    public function __construct(
        $commonResponse,
        $paymentResponse = null,
        $authorizationResponse = null,
        $cardResponse = null,
        $threeDSResponse = null,
        $fraudManagementResponse = null
    ) {
        $this->response['vads_result'] = sprintf('%02d', $commonResponse->getResponseCode());
        $this->response['vads_extra_result'] = ''; // It is directly returned in responseCodeDetail.
        $this->response['vads_message'] = $commonResponse->getResponseCodeDetail();
        $this->response['vads_trans_status'] = $commonResponse->getTransactionStatusLabel();

        if ($paymentResponse) {
            $this->response['vads_warranty_result'] = $paymentResponse->getLiabilityShift();
            $this->response['vads_trans_id'] = $paymentResponse->getTransactionId();
            $this->response['vads_trans_uuid'] = $paymentResponse->getTransactionUuid();
            $this->response['vads_sequence_number'] = $paymentResponse->getSequenceNumber();
            $this->response['vads_operation_type'] = $paymentResponse->getOperationType() == 1 ? 'CREDIT' : 'DEBIT';

            $this->response['vads_currency'] = $paymentResponse->getCurrency();
            $this->response['vads_amount'] = $paymentResponse->getAmount();
            $this->response['vads_effective_amount'] = $paymentResponse->getEffectiveAmount();

            $date = $paymentResponse->getExpectedCaptureDate() ?
                $paymentResponse->getExpectedCaptureDate()->getTimestamp() : time();
            $this->response['vads_presentation_date'] = date('YmdHis', $date);
        }

        if ($authorizationResponse) {
            $this->response['vads_auth_result'] = sprintf('%02d', $authorizationResponse->getResult());
        }

        if ($cardResponse) {
            $this->response['vads_card_brand'] = $cardResponse->getBrand();
            $this->response['vads_card_number'] = $cardResponse->getNumber();
            $this->response['vads_expiry_month'] = $cardResponse->getExpiryMonth();
            $this->response['vads_expiry_year'] = $cardResponse->getExpiryYear();
        }

        if ($threeDSResponse) {
            $this->response['vads_threeds_status'] = $threeDSResponse->getAuthenticationResultData()->getStatus();
            $this->response['vads_threeds_cavv'] = $threeDSResponse->getAuthenticationResultData()->getCavv();
        }

        if ($fraudManagementResponse) {
            $this->response['vads_risk_control'] = $fraudManagementResponse->getRiskControl();
            $this->response['vads_risk_assessment_result'] = $fraudManagementResponse->getRiskAssessments() ?
                $fraudManagementResponse->getRiskAssessments()->getResults() : '';
        }
    }

    public function getResponseParams()
    {
        return  $this->response;
    }
}
