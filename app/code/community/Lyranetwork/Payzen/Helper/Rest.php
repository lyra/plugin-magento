<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Helper_Rest extends Mage_Core_Helper_Abstract
{
    public function convertRestResult($answer)
    {
        if (! is_array($answer) || empty($answer)) {
            return [];
        }

        $transactions = $this->getProperty($answer, 'transactions');

        if (! is_array($transactions) || empty($transactions)) {
            return [];
        }

        $transaction = $transactions[0];

        $response = [];

        $response['vads_result'] = $this->getProperty($transaction, 'errorCode') ? $this->getProperty($transaction, 'errorCode') : '00';
        $response['vads_extra_result'] = $this->getProperty($transaction, 'detailedErrorCode');

        $response['vads_trans_status'] = $this->getProperty($transaction, 'detailedStatus');
        $response['vads_trans_uuid'] = $this->getProperty($transaction, 'uuid');
        $response['vads_operation_type'] = $this->getProperty($transaction, 'operationType');
        $response['vads_effective_creation_date'] = $this->getProperty($transaction, 'creationDate');
        $response['vads_payment_config'] = 'SINGLE'; // Only single payments are possible via REST API at this time.

        if (($customer = $this->getProperty($answer, 'customer')) && ($billingDetails = $this->getProperty($customer, 'billingDetails'))) {
            $response['vads_language'] = $this->getProperty($billingDetails, 'language');
        }

        $response['vads_amount'] = $this->getProperty($transaction, 'amount');
        $response['vads_currency'] = Lyranetwork_Payzen_Model_Api_Api::getCurrencyNumCode($this->getProperty($transaction, 'currency'));

        if ($paymentToken = $this->getProperty($transaction, 'paymentMethodToken')) {
            $response['vads_identifier'] = $paymentToken;
            $response['vads_identifier_status'] = 'CREATED';
        }

        if ($orderDetails = $this->getProperty($answer, 'orderDetails')) {
            $response['vads_order_id'] = $this->getProperty($orderDetails, 'orderId');
        }

        if (($metadata = $this->getProperty($transaction, 'metadata')) && is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $response['vads_ext_info_' . $key] = $value;
            }
        }

        if ($transactionDetails = $this->getProperty($transaction, 'transactionDetails')) {
            $response['vads_sequence_number'] = $this->getProperty($transactionDetails, 'sequenceNumber');
            $response['vads_effective_amount'] = $this->getProperty($transactionDetails, 'effectiveAmount');
            $response['vads_effective_currency'] = Lyranetwork_Payzen_Model_Api_Api::getCurrencyNumCode($this->getProperty($transactionDetails, 'effectiveCurrency'));
            $response['vads_warranty_result'] = $this->getProperty($transactionDetails, 'liabilityShift');

            if ($cardDetails = $this->getProperty($transactionDetails, 'cardDetails')) {
                $response['vads_trans_id'] = $this->getProperty($cardDetails, 'legacyTransId'); // deprecated
                $response['vads_presentation_date'] = $this->getProperty($cardDetails, 'expectedCaptureDate');

                $response['vads_card_brand'] = $this->getProperty($cardDetails, 'effectiveBrand');
                $response['vads_card_number'] = $this->getProperty($cardDetails, 'pan');
                $response['vads_expiry_month'] = $this->getProperty($cardDetails, 'expiryMonth');
                $response['vads_expiry_year'] = $this->getProperty($cardDetails, 'expiryYear');

                if ($authorizationResponse = $this->getProperty($cardDetails, 'authorizationResponse')) {
                    $response['vads_auth_result'] = $this->getProperty($authorizationResponse, 'authorizationResult');
                }

                if (($threeDSResponse = $this->getProperty($cardDetails, 'threeDSResponse'))
                    && ($authenticationResultData = $this->getProperty($threeDSResponse, 'authenticationResultData'))) {
                        $response['vads_threeds_cavv'] = $this->getProperty($authenticationResultData, 'cavv');
                        $response['vads_threeds_status'] = $this->getProperty($authenticationResultData, 'status');
                    }
            }

            if ($fraudManagement = $this->getProperty($transactionDetails, 'fraudManagement')) {
                if ($riskControl = $this->getProperty($fraudManagement, 'riskControl')) {
                    $response['vads_risk_control'] = '';

                    foreach ($riskControl as $key => $value) {
                        $response['vads_risk_control'] .= "$key=$value;";
                    }
                }

                if ($riskAssessments = $this->getProperty($fraudManagement, 'riskAssessments')) {
                    $response['vads_risk_assessment_result'] = $this->getProperty($riskAssessments, 'results');
                }
            }
        }

        return $response;
    }

    private function getProperty($paymentResult, $key)
    {
        if (isset($paymentResult[$key])) {
            return $paymentResult[$key];
        }

        return null;
    }

    public function checkResponseFormat($data)
    {
        return isset($data['kr-hash']) && isset($data['kr-hash-algorithm']) && isset($data['kr-answer']);
    }

    public function checkResponseHash($data, $key)
    {
        $supportedSignAlgos = array('sha256_hmac');

        // check if the hash algorithm is supported
        if (! in_array($data['kr-hash-algorithm'], $supportedSignAlgos)) {
            $this->_getHelper->log('Hash algorithm is not supported: ' . $data['kr-hash-algorithm'], \Psr\Log\LogLevel::ERROR);
            return false;
        }

        // on some servers, / can be escaped
        $krAnswer = str_replace('\/', '/', $data['kr-answer']);

        $hash = hash_hmac('sha256', $krAnswer, $key);

        // return true if calculated hash and sent hash are the same
        return ($hash === $data['kr-hash']);
    }

    private function _getPassword($isTest = true)
    {
        $standard = Mage::getModel('payzen/payment_standard');
        $crypted = $standard->getConfigData($isTest ? 'rest_private_key_test' : 'rest_private_key_prod');

        return Mage::helper('core')->decrypt($crypted);
    }

    /**
     * Get REST API HAMC-SHA-256 return key.
     *
     * @param boolean $isTest
     * @return string
     */
    public function getReturnKey($isTest = true)
    {
        $standard = Mage::getModel('payzen/payment_standard');
        $crypted = $standard->getConfigData($isTest ? 'rest_return_key_test' : 'rest_return_key_prod');

        return Mage::helper('core')->decrypt($crypted);
    }

    protected function _getHelper()
    {
        return Mage::helper('payzen');
    }
}
