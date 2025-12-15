<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Helper;

use Lyranetwork\Payzen\Model\Api\Form\Api as PayzenApi;

class Rest
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     */
    public function __construct(
        \Lyranetwork\Payzen\Helper\Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    public function convertRestResult($answer, $isTransaction = false)
    {
        if (! is_array($answer) || empty($answer)) {
            return [];
        }

        $transactions = $this->getProperty($answer, 'transactions');
        $multiPaymentMean = false;

        if ($isTransaction) {
            $transaction = $answer;
        } else {
            if (! is_array($transactions) || empty($transactions)) {
                $transaction = $answer;
            } else {
                $transaction = $transactions[0];

                $transactionsFiltered = array_filter($transactions, function($trs) {
                    $successStatuses = array_merge(PayzenApi::getSuccessStatuses(), PayzenApi::getPendingStatuses());

                    return $trs['operationType'] === 'DEBIT' && in_array($trs['detailedStatus'], $successStatuses);
                });

                if (count($transactionsFiltered) > 1) {
                    $multiPaymentMean = isset($transactionsFiltered[0]['transactionDetails']['cardDetails']['sequenceType'])
                        && $transactionsFiltered[0]['transactionDetails']['cardDetails']['sequenceType'] == 'MULTI_PAYMENT_MEAN';
                }
            }
        }

        $response = [];

        $response['vads_url_check_src'] = $this->getProperty($answer, 'kr-src');
        $response['vads_order_cycle'] = $this->getProperty($answer, 'orderCycle');
        $response['vads_order_status'] = $this->getProperty($answer, 'orderStatus');

        if (($customer = $this->getProperty($answer, 'customer'))) {
            $response['vads_cust_email'] = $this->getProperty($customer, 'email');

            if ($billingDetails = $this->getProperty($customer, 'billingDetails')) {
                $response['vads_language'] = $this->getProperty($billingDetails, 'language');
            }
        }

        if ($orderDetails = $this->getProperty($answer, 'orderDetails')) {
            $response['vads_order_id'] = $this->getProperty($orderDetails, 'orderId');
        }

        $response = $this->convertRestTransaction($response, $transaction);

        if ($multiPaymentMean) {
            $payment_seq = array(
                'trans_id' => $response['vads_trans_id'],
                'transactions' => array()
            );

            foreach ($transactions as $trs) {
                $payment_seq['transactions'][] = self::convertRestTransaction(array(), $trs, '');
            }

            $response['vads_card_brand'] = 'MULTI';
            $response['vads_payment_seq'] = json_encode($payment_seq);
            $response['vads_amount'] = self::getProperty($orderDetails, 'orderTotalAmount');
            $response['vads_authorized_amount'] = self::getProperty($orderDetails, 'orderTotalAmount');
        }

        return $response;
    }

    private function convertRestTransaction($response, $transaction, $prefix = 'vads_') {
        $response[$prefix . 'result'] = $this->getProperty($transaction, 'errorCode') ? $this->getProperty($transaction, 'errorCode') : '00';
        $response[$prefix . 'extra_result'] = $this->getProperty($transaction, 'detailedErrorCode');

        if ($errorMessage = $this->getErrorMessage($transaction)) {
            $response[$prefix . 'error_message'] = $errorMessage;
        }

        $response[$prefix . 'trans_status'] = $this->getProperty($transaction, 'detailedStatus');
        $response[$prefix . 'trans_uuid'] = $this->getProperty($transaction, 'uuid');
        $response[$prefix . 'operation_type'] = $this->getProperty($transaction, 'operationType');
        $response[$prefix . 'effective_creation_date'] = $this->getProperty($transaction, 'creationDate');
        $response[$prefix . 'payment_config'] = 'SINGLE'; // Only single payments are possible via REST API at this time.

        $response[$prefix . 'amount'] = $this->getProperty($transaction, 'amount');
        $response[$prefix . 'currency'] = PayzenApi::getCurrencyNumCode($this->getProperty($transaction, 'currency'));

        if ($paymentToken = $this->getProperty($transaction, 'paymentMethodToken')) {
            $response[$prefix . 'identifier'] = $paymentToken;
            $response[$prefix . 'identifier_status'] = 'CREATED';
        }

        if (($metadata = $this->getProperty($transaction, 'metadata')) && is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $response[$prefix . 'ext_info_' . $key] = $value;
            }
        }

        if ($transactionDetails = $this->getProperty($transaction, 'transactionDetails')) {
            $response[$prefix . 'sequence_number'] = $this->getProperty($transactionDetails, 'sequenceNumber');

            // Workarround to adapt to REST API behavior.
            $effectiveAmount = $this->getProperty($transactionDetails, 'effectiveAmount');
            $effectiveCurrency = PayzenApi::getCurrencyNumCode($this->getProperty($transactionDetails, 'effectiveCurrency'));

            if ($effectiveAmount && $effectiveCurrency) {
                // Invert only if there is currency conversion.
                if ($effectiveCurrency !== $response[$prefix . 'currency']) {
                    $response[$prefix . 'effective_amount'] = $response[$prefix . 'amount'];
                    $response[$prefix . 'effective_currency'] = $response[$prefix . 'currency'];
                    $response[$prefix . 'amount'] = $effectiveAmount;
                    $response[$prefix . 'currency'] = $effectiveCurrency;
                } else {
                    $response[$prefix . 'effective_amount'] = $effectiveAmount;
                    $response[$prefix . 'effective_currency'] = $effectiveCurrency;
                }
            }

            $response[$prefix . 'warranty_result'] = $this->getProperty($transactionDetails, 'liabilityShift');
            $response[$prefix . 'wallet'] = $this->getProperty($transactionDetails, 'wallet');

            if ($cardDetails = $this->getProperty($transactionDetails, 'cardDetails')) {
                $response[$prefix . 'trans_id'] = $this->getProperty($cardDetails, 'legacyTransId'); // Deprecated.
                $response[$prefix . 'presentation_date'] = $this->getProperty($cardDetails, 'expectedCaptureDate');

                $response[$prefix . 'card_brand'] = $this->getProperty($cardDetails, 'effectiveBrand');
                $response[$prefix . 'card_number'] = $this->getProperty($cardDetails, 'pan');
                $response[$prefix . 'expiry_month'] = $this->getProperty($cardDetails, 'expiryMonth');
                $response[$prefix . 'expiry_year'] = $this->getProperty($cardDetails, 'expiryYear');

                $response[$prefix . 'payment_option_code'] = $this->getProperty($cardDetails, 'installmentNumber');


                if ($authorizationResponse = $this->getProperty($cardDetails, 'authorizationResponse')) {
                    $response[$prefix . 'auth_result'] = $this->getProperty($authorizationResponse, 'authorizationResult');
                    $response[$prefix . 'authorized_amount'] = $this->getProperty($authorizationResponse, 'amount');
                }

                if (($authenticationResponse = self::getProperty($cardDetails, 'authenticationResponse'))
                    && ($value = self::getProperty($authenticationResponse, 'value'))) {
                    $response[$prefix . 'threeds_status'] = self::getProperty($value, 'status');
                    $response[$prefix . 'threeds_auth_type'] = self::getProperty($value, 'authenticationType');
                    if ($authenticationValue = self::getProperty($value, 'authenticationValue')) {
                        $response[$prefix . 'threeds_cavv'] = self::getProperty($authenticationValue, 'value');
                    }
                } elseif (($threeDSResponse = self::getProperty($cardDetails, 'threeDSResponse'))
                    && ($authenticationResultData = self::getProperty($threeDSResponse, 'authenticationResultData'))) {
                    $response[$prefix . 'threeds_cavv'] = self::getProperty($authenticationResultData, 'cavv');
                    $response[$prefix . 'threeds_status'] = self::getProperty($authenticationResultData, 'status');
                    $response[$prefix . 'threeds_auth_type'] = self::getProperty($authenticationResultData, 'threeds_auth_type');
                }
            }

            if ($fraudManagement = $this->getProperty($transactionDetails, 'fraudManagement')) {
                if ($riskControl = $this->getProperty($fraudManagement, 'riskControl')) {
                    $response[$prefix . 'risk_control'] = '';

                    foreach ($riskControl as $value) {
                        if (! isset($value['name']) || ! isset($value['result'])) {
                            continue;
                        }

                        $response[$prefix . 'risk_control'] .= "{$value['name']}={$value['result']};";
                    }
                }

                if ($riskAssessments = $this->getProperty($fraudManagement, 'riskAssessments')) {
                    $response[$prefix . 'risk_assessment_result'] = $this->getProperty($riskAssessments, 'results');
                }
            }
        }

        return $response;
    }

    private function getErrorMessage($transaction)
    {
        $code = $this->getProperty($transaction, 'errorCode');
        if ($code) {
            return ucfirst($this->getProperty($transaction, 'errorMessage')) . ' (' . $code . ').';
        }

        return null;
    }

    public function checkResult($response, $expectedStatuses = [])
    {
        $answer = $response['answer'];

        if ($response['status'] !== 'SUCCESS') {
            $msg = $answer['errorMessage'] . ' (' . $answer['errorCode'] . ').';
            if (isset($answer['detailedErrorMessage']) && ! empty($answer['detailedErrorMessage'])) {
                $msg .= ' Detailed message: ' . $answer['detailedErrorMessage'] . ($answer['detailedErrorCode'] ?
                    ' (' . $answer['detailedErrorCode'] . ').' : '');
            }
            $this->dataHelper->log('Hash algorithm is not supported: ' . print_r($answer, true), \Psr\Log\LogLevel::ERROR);
            throw new \Lyranetwork\Payzen\Model\RestException($msg, $answer['errorCode']);
        } elseif (! empty($expectedStatuses) && ! in_array($answer['detailedStatus'], $expectedStatuses)) {
            throw new \UnexpectedValueException(
                "Unexpected transaction status returned ({$answer['detailedStatus']})."
            );
        }
    }

    private function getProperty($restResult, $key)
    {
        if (isset($restResult[$key])) {
            return $restResult[$key];
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
            $this->dataHelper->log('Hash algorithm is not supported: ' . $data['kr-hash-algorithm'], \Psr\Log\LogLevel::ERROR);
            return false;
        }

        // On some servers, / can be escaped.
        $krAnswer = str_replace('\/', '/', $data['kr-answer'] ? $data['kr-answer'] : '');

        $hash = hash_hmac('sha256', $krAnswer, $key);

        // Return true if calculated hash and sent hash are the same.
        return ($hash === $data['kr-hash']);
    }

    /**
     * Get REST API SHA 256 return key.
     *
     * @return string
     */
    public function getReturnKey($storeId = null)
    {
        $ctxMode = $this->dataHelper->getCommonConfigData('ctx_mode', $storeId);
        $field = ($ctxMode === 'TEST') ? 'rest_return_key_test' : 'rest_return_key_prod';

        return $this->dataHelper->getCommonConfigData($field, $storeId);
    }

    /**
     * Get REST API private key.
     *
     * @return string
     */
    public function getPrivateKey($storeId = null)
    {
        $ctxMode = $this->dataHelper->getCommonConfigData('ctx_mode', $storeId);
        $field = ($ctxMode === 'TEST') ? 'rest_private_key_test' : 'rest_private_key_prod';

        return $this->dataHelper->getCommonConfigData($field, $storeId);
    }

    public function checkIdentifier($identifier, $customerEmail)
    {
        try {
            $requestData = [
                'paymentMethodToken' => $identifier
            ];

            // Perform REST request to check identifier.
            $client = new \Lyranetwork\Payzen\Model\Api\Rest\Api(
                $this->dataHelper->getCommonConfigData('rest_url'),
                $this->dataHelper->getCommonConfigData('site_id'),
                $this->getPrivateKey()
            );

            $checkIdentifierResponse = $client->post('V4/Token/Get', json_encode($requestData));
            $this->checkResult($checkIdentifierResponse);

            $cancellationDate = $this->getProperty($checkIdentifierResponse['answer'], 'cancellationDate');
            if ($cancellationDate && (strtotime($cancellationDate) <= time())) {
                $this->dataHelper->log(
                    "Saved identifier for customer {$customerEmail} is expired on payment gateway in date of: {$cancellationDate}.",
                    \Psr\Log\LogLevel::WARNING
                );
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $invalidIdentCodes = ['PSP_030', 'PSP_031', 'PSP_561', 'PSP_607'];

            if (in_array($e->getCode(), $invalidIdentCodes)) {
                // The identifier is invalid or doesn't exist.
                $this->dataHelper->log(
                    "Identifier for customer {$customerEmail} is invalid or doesn't exist: {$e->getMessage()}.",
                     \Psr\Log\LogLevel::WARNING
                );
                return false;
            } else {
                throw $e;
            }
        }
    }
}
