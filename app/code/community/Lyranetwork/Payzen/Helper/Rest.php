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
    public function convertRestResult($answer, $isTransaction = false)
    {
        if (! is_array($answer) || empty($answer)) {
            return array();
        }

        if ($isTransaction){
            $transaction = $answer;
        } else{
            $transactions = $this->getProperty($answer, 'transactions');

            if (! is_array($transactions) || empty($transactions)) {
                return array();
            }

            $transaction = $transactions[0];
        }

        $response = array();

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

        $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrency($this->getProperty($transaction, 'currency'));
        $response['vads_currency'] = Lyranetwork_Payzen_Model_Api_Api::getCurrencyNumCode($currency->getAlpha3());

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

            $effectiveAmount = $this->getProperty($transactionDetails, 'effectiveAmount');
            $effectiveCurrency = Lyranetwork_Payzen_Model_Api_Api::getCurrencyNumCode($this->getProperty($transactionDetails, 'effectiveCurrency'));

            // Workarround to adapt to REST API behavior.
            if ($effectiveAmount && $effectiveCurrency) {
                $response['vads_effective_amount'] = $response['vads_amount'];
                $response['vads_effective_currency'] = $response['vads_currency'];
                $response['vads_amount'] = $effectiveAmount;
                $response['vads_currency'] = $effectiveCurrency;
            }

            $response['vads_warranty_result'] = $this->getProperty($transactionDetails, 'liabilityShift');

            if ($cardDetails = $this->getProperty($transactionDetails, 'cardDetails')) {
                $response['vads_trans_id'] = $this->getProperty($cardDetails, 'legacyTransId'); // Deprecated.
                $response['vads_presentation_date'] = $this->getProperty($cardDetails, 'expectedCaptureDate');

                $response['vads_card_brand'] = $this->getProperty($cardDetails, 'effectiveBrand');
                $response['vads_card_number'] = $this->getProperty($cardDetails, 'pan');
                $response['vads_expiry_month'] = $this->getProperty($cardDetails, 'expiryMonth');
                $response['vads_expiry_year'] = $this->getProperty($cardDetails, 'expiryYear');

                if ($authorizationResponse = $this->getProperty($cardDetails, 'authorizationResponse')) {
                    $response['vads_auth_result'] = $this->getProperty($authorizationResponse, 'authorizationResult');
                }

                if (($authenticationResponse = self::getProperty($cardDetails, 'authenticationResponse'))
                    && ($value = self::getProperty($authenticationResponse, 'value'))) {
                    $response['vads_threeds_status'] = self::getProperty($value, 'status');
                    $response['vads_threeds_auth_type'] = self::getProperty($value, 'authenticationType');
                    if ($authenticationValue = self::getProperty($value, 'authenticationValue')) {
                        $response['vads_threeds_cavv'] = self::getProperty($authenticationValue, 'value');
                    }
                } elseif (($threeDSResponse = $this->getProperty($cardDetails, 'threeDSResponse'))
                    && ($authenticationResultData = $this->getProperty($threeDSResponse, 'authenticationResultData'))) {
                    $response['vads_threeds_cavv'] = $this->getProperty($authenticationResultData, 'cavv');
                    $response['vads_threeds_status'] = $this->getProperty($authenticationResultData, 'status');
                    $response['vads_threeds_auth_type'] = self::getProperty($authenticationResultData, 'threeds_auth_type');
                }
            }

            if ($fraudManagement = $this->getProperty($transactionDetails, 'fraudManagement')) {
                if ($riskControl = $this->getProperty($fraudManagement, 'riskControl')) {
                    $response['vads_risk_control'] = '';

                    foreach ($riskControl as $value) {
                        $response['vads_risk_control'] .= "{$value['name']}={$value['result']};";
                    }
                }

                if ($riskAssessments = $this->getProperty($fraudManagement, 'riskAssessments')) {
                    $response['vads_risk_assessment_result'] = $this->getProperty($riskAssessments, 'results');
                }
            }
        }

        return $response;
    }

    public function checkResult($response, $expectedStatuses = array())
    {
        $answer = $response['answer'];

        if ($response['status'] != 'SUCCESS') {
            $errorMessage = $answer['errorMessage'] . ' (' . $answer['errorCode'] . ').';

            if (isset($answer['detailedErrorMessage']) && ! empty($answer['detailedErrorMessage'])) {
                $errorMessage .= ' Detailed message: ' . $answer['detailedErrorMessage'] . ($answer['detailedErrorCode'] ?
                    ' (' . $answer['detailedErrorCode'] . ').' : '');
            }

            throw new Lyranetwork_Payzen_Model_RestException($errorMessage, $answer['errorCode']);
        } elseif (! empty($expectedStatuses) && ! in_array($answer['detailedStatus'], $expectedStatuses)) {
            $errorMessage = $this->_getHelper()->__("Unexpected transaction status received (%s).", $answer['detailedStatus']);
            throw new UnexpectedValueException($errorMessage);
        }
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

        // Check if the hash algorithm is supported.
        if (! in_array($data['kr-hash-algorithm'], $supportedSignAlgos)) {
            $this->_getHelper()->log('Hash algorithm is not supported: ' . $data['kr-hash-algorithm'], Zend_Log::ERR);
            return false;
        }

        // On some servers, / can be escaped.
        $krAnswer = str_replace('\/', '/', $data['kr-answer']);

        $hash = hash_hmac('sha256', $krAnswer, $key);

        // Return true if calculated hash and sent hash are the same.
        return ($hash === $data['kr-hash']);
    }

    public function getPassword($storeId = null)
    {
        $isTest = $this->_getHelper()->getCommonConfigData('ctx_mode', $storeId) === 'TEST';
        $crypted = $this->_getHelper()->getCommonConfigData($isTest ? 'rest_private_key_test' : 'rest_private_key_prod', $storeId);

        return Mage::helper('core')->decrypt($crypted);
    }

    /**
     * Get REST API HAMC-SHA-256 return key.
     *
     * @param boolean $isTest
     * @return string
     */
    public function getReturnKey($storeId = null)
    {
        $isTest = $this->_getHelper()->getCommonConfigData('ctx_mode', $storeId) === 'TEST';
        $crypted = $this->_getHelper()->getCommonConfigData($isTest ? 'rest_return_key_test' : 'rest_return_key_prod', $storeId);

        return Mage::helper('core')->decrypt($crypted);
    }

    public function checkIdentifier($identifier, $customerEmail)
    {
        try {
            $requestData = array(
                'paymentMethodToken' => $identifier
            );

            // Perform REST request to check identifier.
            $client = new Lyranetwork_Payzen_Model_Api_Rest(
                $this->_getHelper()->getCommonConfigData('rest_url'),
                $this->_getHelper()->getCommonConfigData('site_id'),
                $this->getPassword()
            );

            $checkIdentifierResponse = $client->post('V4/Token/Get', json_encode($requestData));
            $this->checkResult($checkIdentifierResponse);

            $cancellationDate = $this->getProperty($checkIdentifierResponse['answer'], 'cancellationDate');
            if ($cancellationDate && (strtotime($cancellationDate) <= time())) {
                $this->_getHelper()->log(
                    "Saved identifier for customer {$customerEmail} is expired on payment gateway in date of: {$cancellationDate}.",
                    Zend_Log::WARN
                );
                return false;
            }

            return true;
        } catch (Exception $e) {
            $invalidIdentCodes = array('PSP_030', 'PSP_031', 'PSP_561', 'PSP_607');

            if (in_array($e->getCode(), $invalidIdentCodes)) {
                // The identifier is invalid or doesn't exist.
                $this->_getHelper()->log(
                    "Identifier for customer {$customerEmail} is invalid or doesn't exist: {$e->getMessage()}.",
                    Zend_Log::WARN
                );
                return false;
            } else {
                throw $e;
            }
        }
    }

    public function setSessionValidPaymentByToken($customer)
    {
        try {
            $isValidIdentifier = $this->checkIdentifier($customer->getPayzenIdentifier(), $customer->getEmail());
        } catch (Exception $e) {
            $this->_getHelper()->log(
                "Saved identifier for customer {$customer->getEmail()} couldn't be verified on gateway. Error occurred: {$e->getMessage()}",
                Zend_Log::ERR
            );

            // Unable to validate alias online, we cannot disable feature.
            $isValidIdentifier = true;
        }

        Mage::getSingleton('checkout/session')->setValidAlias($isValidIdentifier);
    }

    protected function _getHelper()
    {
        return Mage::helper('payzen');
    }
}
