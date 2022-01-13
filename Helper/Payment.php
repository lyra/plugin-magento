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

use Lyranetwork\Payzen\Model\Api\PayzenApi;

class Payment
{
    // Key to save if payment is by identifier.
    const IDENTIFIER = 'payzen_identifier';
    const SEPA_IDENTIFIER = 'payzen_identifier';

    // Key to save choosen multi option.
    const MULTI_OPTION = 'payzen_multi_option';

    // Key to save choosen Choozeo option.
    const CHOOZEO_OPTION = 'payzen_choozeo_option';

    // Key to save choosen Oney option.
    const ONEY_OPTION = 'payzen_oney_option';

    // Key to save choosen Full CB option.
    const FULLCB_OPTION = 'payzen_fullcb_option';

    // Key to save choosen Franfinance option.
    const FRANFINANCE_OPTION = 'payzen_franfinance_option';

    // Key to save choosen other payment means.
    const OTHER_OPTION = 'payzen_other_option';

    // Key to save risk control results.
    const RISK_CONTROL = 'payzen_risk_control';

    // Key to save risk assessment results.
    const RISK_ASSESSMENT = 'payzen_risk_assessment';

    // Key to save payment results.
    const ALL_RESULTS = 'payzen_all_results';

    // Key to save Rest Api error message.
    const REST_ERROR_MESSAGE = 'payzen_rest_error';

    const TRANS_UUID = 'payzen_trans_uuid';

    const BRAND_USER_CHOICE = 'payzen_brand_user_choice';

    const SUCCESS = 1;
    const FAILURE = 2;
    const CANCEL = 3;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface
     */
    protected $transactionManager;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
     protected $customerFactory;

     /**
      * @var \Magento\Customer\Model\ResourceModel\CustomerFactory
      */
     protected $customerResourceFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Lyranetwork\Payzen\Helper\Rest
     */
    protected $restHelper;

    /**
     * \Magento\Framework\DataObject\Factory
     */
    protected $dataObjectFactory;

    /**
     * \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface $transactionManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Rest $restHelper
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface $transactionManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Rest $restHelper,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->eventManager = $eventManager;
        $this->transactionRepository = $transactionRepository;
        $this->transactionManager = $transactionManager;
        $this->customerFactory = $customerFactory;
        $this->customerResourceFactory = $customerResourceFactory;
        $this->orderSender = $orderSender;
        $this->dataHelper = $dataHelper;
        $this->restHelper = $restHelper;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->orderConfig = $orderConfig;
        $this->timezone = $timezone;
    }

    /**
     * Update order status and eventually create invoice.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Lyranetwork\Payzen\Model\Api\PayzenResponse $response
     */
    public function registerOrder(
        \Magento\Sales\Model\Order $order,
        \Lyranetwork\Payzen\Model\Api\PayzenResponse $response
    ) {
        $this->dataHelper->log("Saving payment for order #{$order->getIncrementId()}.");

        // Update authorized amount.
        $order->getPayment()->setAmountAuthorized($order->getTotalDue());
        $order->getPayment()->setBaseAmountAuthorized($order->getBaseTotalDue());

        // Retrieve new order state and status.
        $stateObject = $this->nextOrderState($order, $response);

        $this->dataHelper->log("Order #{$order->getIncrementId()}, new state: {$stateObject->getState()}," .
             " new status: {$stateObject->getStatus()}.");
        $order->setState($stateObject->getState())
            ->setStatus($stateObject->getStatus())
            ->addStatusHistoryComment($response->get('error_message') ?: $response->getMessage());

        // Save gateway responses.
        $this->updatePaymentInfo($order, $response);

        // Try to save gateway identifier if any.
        $method = $order->getPayment()->getMethodInstance();
        if ($method instanceof \Lyranetwork\Payzen\Model\Method\Sepa)  {
            $this->saveSepaIdentifier($order, $response);
        } else {
            $this->saveIdentifier($order, $response);
        }

        // Try to create invoice.
        $this->createInvoice($order);

        $this->dataHelper->log("Saving confirmed order #{$order->getIncrementId()} and sending e-mail if not disabled.");
        $order->save();
        $this->dataHelper->log("Confirmed order #{$order->getIncrementId()} has been saved.");

        if ($order->getSendEmail() === null /* not set */ || $order->getSendEmail() /* set to true */) {
            $this->dataHelper->log("Sending e-mail for order #{$order->getIncrementId()}.");
            $this->orderSender->send($order);
            $this->dataHelper->log("E-mail for order #{$order->getIncrementId()} has been sent.");
        }
    }

    /**
     * Get new order state and status according to gateway response.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Lyranetwork\Payzen\Model\Api\PayzenResponse $response
     * @param boolean $ignoreFraud
     * @return \Magento\Framework\DataObject
     */
    public function nextOrderState(
        \Magento\Sales\Model\Order $order,
        \Lyranetwork\Payzen\Model\Api\PayzenResponse $response,
        $ignoreFraud = false
    ) {
        if ($response->isToValidatePayment()) {
            $newStatus = 'payzen_to_validate';
            $newState = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
        } elseif ($response->isPendingPayment()) {
            $newStatus = 'payment_review';
            $newState = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
        } else {
            if ($this->isSepa($response)) {
                // Pending funds transfer order state.
                $newStatus = 'payzen_pending_transfer';
            } else {
                $newStatus = $this->dataHelper->getCommonConfigData(
                    'registered_order_status',
                    $order->getStore()->getId()
                );
            }

            $processingStatuses = $this->orderConfig->getStateStatuses(
                \Magento\Sales\Model\Order::STATE_PROCESSING,
                false
            );
            $newState = in_array($newStatus, $processingStatuses) ? \Magento\Sales\Model\Order::STATE_PROCESSING :
                \Magento\Sales\Model\Order::STATE_NEW;
        }

        $stateObject = $this->dataObjectFactory->create();

        if (! $ignoreFraud && $response->isSuspectedFraud()) {
            $stateObject->setBeforeState($newState);
            $stateObject->setBeforeStatus($newStatus);

            $newState = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
            $newStatus = 'fraud';
        }

        $stateObject->setState($newState);
        $stateObject->setStatus($newStatus);
        return $stateObject;
    }

    public function updatePaymentInfo(\Magento\Sales\Model\Order $order, \Lyranetwork\Payzen\Model\Api\PayzenResponse $response)
    {
        $this->dataHelper->log("Updating payment information for order #{$order->getIncrementId()}.");

        // Set common payment information.
        $order->getPayment()
            ->setCcTransId($response->get('trans_id'))
            ->setCcType($response->get('card_brand'))
            ->setCcStatus($response->getResult())
            ->setCcStatusDescription($response->get('error_message') ?: $response->getMessage())
            ->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::ALL_RESULTS, serialize($response->getAllResults()))
            ->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID, $response->get('trans_uuid'));

        $restErrorMsg = $response->get('error_message');
        if ($restErrorMsg) {
            if ($response->get('detailed_error_message')) {
                $restErrorMsg .= ' ' . $response->get('detailed_error_message');
            }

            $order->getPayment()->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::REST_ERROR_MESSAGE, $restErrorMsg);
        }

        $currency = PayzenApi::findCurrencyByNumCode($response->get('currency'));

        // Authorized amount.
        if ($authorizedAmount = $response->get('authorized_amount')) {
            $order->getPayment()->setAdditionalInformation('authorized_amount', $currency->convertAmountToFloat($authorizedAmount) . ' ' . $currency->getAlpha3());
        }

        // Store installments number/config.
        if (($installmentsNumber = $response->get('payment_option_code')) && is_numeric($installmentsNumber)) {
            $order->getPayment()->setAdditionalInformation('payment_option_code', $installmentsNumber);
        }

        // 3DS authentication result.
        $threedsCavv = '';
        $threedsStatus = '';
        $threedsAuthType = '';
        if ($status = $response->get('threeds_status')) {
            $threedsStatus = $this->getThreedsStatus($status);
            $threedsCavv = $response->get('threeds_cavv');
            $threedsAuthType = $response->get('threeds_auth_type');
        }

        // Save payment infos to sales_flat_order_payment.
        $order->getPayment()
            ->setCcSecureVerify($threedsCavv)
            ->setAdditionalInformation('threeds_status', $threedsStatus)
            ->setAdditionalInformation('threeds_auth_type', $threedsAuthType);


        if ($response->isCancelledPayment()) {
            // No more data to save.
            return;
        }

        if ($response->get('brand_management')) {
            $brandInfo = json_decode($response->get('brand_management'));

            $userChoice = (isset($brandInfo->userChoice) && $brandInfo->userChoice);
            $order->getPayment()->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::BRAND_USER_CHOICE, $userChoice);
        }

        // Save risk control result if any.
        $riskControl = $response->getRiskControl();
        if (! empty($riskControl)) {
            $order->getPayment()->setAdditionalInformation(self::RISK_CONTROL, serialize($riskControl));
        }

        // Save risk assessment result if any.
        $riskAssessment = $response->getRiskAssessment();
        if (! empty($riskAssessment)) {
            $order->getPayment()->setAdditionalInformation(self::RISK_ASSESSMENT, serialize($riskAssessment));
        }

        // Set is_fraud_detected flag.
        $order->getPayment()->setIsFraudDetected($response->isSuspectedFraud());

        if ($response->get('card_brand') === 'MULTI') { // Multi brand.
            $data = json_decode($response->get('payment_seq'));
            $transactions = $data->{'transactions'};

            $userChoice = [];

            // Save transaction details to sales_payment_transaction.
            foreach ($transactions as $trs) {
                // Save transaction details to sales_payment_transaction.
                $expiry = '';
                if (! empty($trs->{'expiry_month'}) && ! empty($trs->{'expiry_year'})) {
                    $expiry = str_pad($trs->{'expiry_month'}, 2, '0', STR_PAD_LEFT) . ' / ' . $trs->{'expiry_year'};
                }

                $transactionId = $response->get('trans_id') . '-' . $trs->{'sequence_number'};

                // Save paid amount.
                $amount = round($currency->convertAmountToFloat($trs->{'amount'}), $currency->getDecimals());

                $amountDetail = $amount . ' ' . $currency->getAlpha3();

                $additionalInfo = [
                    'Transaction Type' => $trs->{'operation_type'},
                    'Amount' => $amountDetail,
                    'Transaction ID' => $transactionId,
                    'Transaction UUID' => $trs->{'trans_uuid'},
                    'Extra Transaction ID' => property_exists($trs, 'ext_trans_id') && isset($trs->{'ext_trans_id'}) ? $trs->{'ext_trans_id'} : '',
                    'Transaction Status' => $trs->{'trans_status'},
                    'Means of payment' => $trs->{'card_brand'},
                    'Card Number' => $trs->{'card_number'},
                    'Expiration Date' => $expiry
                ];

                $transactionType = $this->convertTransactionType($trs->{'trans_status'});

                $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);

                if (isset($trs->{'brand_management'}) && ($brandInfo = $trs->{'brand_management'})) {
                    $userChoice[$trs->{'sequence_number'}] = (isset($brandInfo->userChoice) && $brandInfo->userChoice);
                }
            }

            $order->getPayment()->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::BRAND_USER_CHOICE, $userChoice);
        } else {
            // Save payment infos to sales_flat_order_payment.
            $order->getPayment()
                ->setCcExpMonth($response->get('expiry_month'))
                ->setCcExpYear($response->get('expiry_year'))
                ->setCcNumberEnc($response->get('card_number'));

            // Save transaction details to sales_payment_transaction.
            $expiry = '';
            if ($response->get('expiry_month') && $response->get('expiry_year')) {
                $expiry = str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT) . ' / ' .
                     $response->get('expiry_year');
            }

            // Magento transaction type.
            $transactionType = $this->convertTransactionType($response->getTransStatus());

            $timestamp = strtotime($response->get('presentation_date') . ' UTC');
            $date = new \DateTime();

            // Total payment amount.
            $totalAmount = (int) $response->get('amount');

            // Get choosen payment option if any.
            $option = @unserialize($order->getPayment()->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION));

            // Check if it's the first installment.
            $isFirstInstallment = ((int)$response->get('sequence_number') === 1)
                || (strpos($response->get('payment_config'), 'MULTI') !== false);

            if ($isFirstInstallment && (stripos($order->getPayment()->getMethod(), 'payzen_multi') === 0)
                && is_array($option) && ! empty($option)) {
                $count = (int) $option['count'];

                if (isset($option['first']) && $option['first']) {
                    $firstAmount = round($totalAmount * $option['first'] / 100);
                } else {
                    $firstAmount = round($totalAmount / $count);
                }

                // Installment amount, double cast to avoid rounding.
                $installmentAmount = (int) (string) (($totalAmount - $firstAmount) / ($count - 1));

                $firstSeqNum = $response->get('sequence_number') ? (int) $response->get('sequence_number') : 1;
                for ($i = 1; $i <= $count; $i++) {
                    $transactionId = $response->get('trans_id') . '-' . ($firstSeqNum + $i - 1);

                    $delay = (int) $option['period'] * ($i - 1);
                    $date->setTimestamp(strtotime("+$delay days", $timestamp));

                    switch (true) {
                        case ($i === 1): // First transaction.
                            $amount = $firstAmount;
                            break;

                        case ($i === $count): // Last transaction.
                            $amount = $totalAmount - $firstAmount - $installmentAmount * ($i - 2);
                            break;

                        default: // Others.
                            $amount = $installmentAmount;
                            break;
                    }

                    $floatAmount = round($currency->convertAmountToFloat($amount), $currency->getDecimals());
                    $amountDetail = $floatAmount . ' ' . $currency->getAlpha3();

                    if (($rate = $response->get('change_rate')) && $response->get('effective_currency') &&
                        ($response->get('currency') !== $response->get('effective_currency'))) {
                        // Effective amount.
                        $effectiveCurrency = PayzenApi::findCurrencyByNumCode($response->get('effective_currency'));

                        $effectiveAmount = round(
                            $effectiveCurrency->convertAmountToFloat(round($amount / $rate)),
                            $effectiveCurrency->getDecimals()
                        );

                        $amountDetail = $effectiveAmount . ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
                    }

                    $additionalInfo = [
                        'Transaction Type' => $response->get('operation_type'),
                        'Amount' => $amountDetail,
                        'Presentation Date' => $this->timezone->formatDateTime(
                            $date,
                            \IntlDateFormatter::MEDIUM,
                            \IntlDateFormatter::NONE
                        ),
                        'Transaction ID' => $transactionId,
                        'Transaction UUID' => ($i === 1) ? $response->get('trans_uuid') : '',
                        'Transaction Status' => ($i === 1) ? $response->getTransStatus() : $this->getNextTransStatus($response->getTransStatus()),
                        'Means of payment' => $response->get('card_brand'),
                        'Card Number' => $response->get('card_number'),
                        'Expiration Date' => $expiry,
                        '3DS Authentication' => $threedsStatus,
                        '3DS Certificate' => $threedsCavv,
                        'Authentication Type' => $threedsAuthType
                    ];

                    $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
                }
            } else {
                // Save transaction details to sales_payment_transaction.
                $transactionId = $response->get('trans_id') . '-' . $response->get('sequence_number');

                $floatAmount = round($currency->convertAmountToFloat($totalAmount), $currency->getDecimals());
                $amountDetail = $floatAmount . ' ' . $currency->getAlpha3();

                if ($response->get('effective_currency') &&
                    ($response->get('currency') !== $response->get('effective_currency'))) {
                    // Effective amount.
                    $effectiveCurrency = PayzenApi::findCurrencyByNumCode($response->get('effective_currency'));

                    $effectiveAmount = round(
                        $effectiveCurrency->convertAmountToFloat($response->get('effective_amount')),
                        $effectiveCurrency->getDecimals()
                    );

                    $amountDetail = $effectiveAmount . ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
                }

                $date->setTimestamp($timestamp);

                $additionalInfo = [
                    'Transaction Type' => $response->get('operation_type'),
                    'Amount' => $amountDetail,
                    'Presentation Date' => $this->timezone->formatDateTime(
                        $date,
                        \IntlDateFormatter::MEDIUM,
                        \IntlDateFormatter::NONE
                    ),
                    'Transaction ID' => $transactionId,
                    'Transaction UUID' => $response->get('trans_uuid'),
                    'Transaction Status' => $response->getTransStatus(),
                    'Means of payment' => $response->get('card_brand'),
                    'Card Number' => $response->get('card_number'),
                    'Expiration Date' => $expiry,
                    '3DS Authentication' => $threedsStatus,
                    '3DS Certificate' => $threedsCavv,
                    'Authentication Type' => $threedsAuthType
                ];

                $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
            }
        }

        // Skip automatic transaction creation.
        $order->getPayment()
            ->setTransactionId(null)
            ->setSkipTransactionCreation(true);

       $this->dataHelper->log("Payment information updated for order #{$order->getIncrementId()}.");
    }

    private function getThreedsStatus($status)
    {
        switch ($status) {
            case 'Y':
                return 'SUCCESS';

            case 'N':
                return 'FAILED';

            case 'U':
                return 'UNAVAILABLE';

            case 'A':
                return 'ATTEMPT';

            default :
                return $status;
        }
    }

    public function saveIdentifier(
        \Magento\Sales\Model\Order $order,
        \Lyranetwork\Payzen\Model\Api\PayzenResponse $response
    ) {
        if (! $order->getCustomerId()) {
            return;
        }

        if ($response->get('identifier') && (
            $response->get('identifier_status') === 'CREATED' /* page_action REGISTER_PAY or ASK_REGISTER_PAY */ ||
            $response->get('identifier_status') === 'UPDATED' /* page_action REGISTER_UPDATE_PAY */
        )) {
            $customer = $this->customerFactory->create()->load($order->getCustomerId());

            $customerData = $customer->getDataModel();
            $customerData->setId($customer->getId());

            $this->dataHelper->log("Identifier for customer #{$customer->getId()} successfully created" .
                 ' or updated on payment gateway. Let us save it to customer entity.');

            $customerData->setCustomAttribute('payzen_identifier', $response->get('identifier'));

            // Mask card number and save it to customer entity.
            $customerData->setCustomAttribute('payzen_masked_pan', $this->maskPan($response));

            try {
                $customer->updateData($customerData);

                $customerResource = $this->customerResourceFactory->create();
                $customerResource->saveAttribute($customer, 'payzen_identifier');
                $customerResource->saveAttribute($customer, 'payzen_masked_pan');

                $this->dataHelper->log("Identifier for customer #{$customer->getId()} successfully saved to customer entity.");
            } catch (\Exception $e) {
                $this->dataHelper->log(
                    "Identifier for customer #{$customer->getId()} couldn't be saved to customer entity. Error occurred with code {$e->getCode()}: {$e->getMessage()}.",
                    \Psr\Log\LogLevel::ERROR
                );
            }
        }
    }

    public function saveSepaIdentifier(
        \Magento\Sales\Model\Order $order,
        \Lyranetwork\Payzen\Model\Api\PayzenResponse $response
    ) {
        if (! $order->getCustomerId()) {
            return;
        }

        if ($response->get('identifier') && (
            $response->get('identifier_status') === 'CREATED' /* page_action REGISTER_PAY or ASK_REGISTER_PAY */ ||
            $response->get('identifier_status') === 'UPDATED' /* page_action REGISTER_UPDATE_PAY */
        )) {
            $customer = $this->customerFactory->create()->load($order->getCustomerId());

            $customerData = $customer->getDataModel();
            $customerData->setId($customer->getId());

            $this->dataHelper->log("Identifier for customer #{$customer->getId()} successfully created" .
                ' or updated on payment gateway. Let us save it to customer entity.');

            $customerData->setCustomAttribute('payzen_sepa_identifier', $response->get('identifier'));

            // Mask IBAN and save it to customer entity.
            $customerData->setCustomAttribute('payzen_sepa_iban_bic', $this->maskPan($response));

            try {
                $customer->updateData($customerData);

                $customerResource = $this->customerResourceFactory->create();
                $customerResource->saveAttribute($customer, 'payzen_sepa_identifier');
                $customerResource->saveAttribute($customer, 'payzen_sepa_iban_bic');

                $this->dataHelper->log("Identifier for customer #{$customer->getId()} successfully saved to customer entity.");
            } catch (\Exception $e) {
                $this->dataHelper->log(
                    "Identifier for customer #{$customer->getId()} couldn't be saved to customer entity. Error occurred with code {$e->getCode()}: {$e->getMessage()}.",
                    \Psr\Log\LogLevel::ERROR
                );
            }
        }
    }

    public function deleteIdentifier($customerId, $attribute, $maskedAttribute)
    {
        $customer = $this->customerFactory->create()->load($customerId);
        $customerData = $customer->getDataModel();
        $customerData->setId($customerId);

        if (! $customerData->getCustomAttribute($attribute)) {
            $this->dataHelper->log("Customer {$customer->getEmail()} doesn't have a saved {$attribute} attribute.");
            return false;
        }

        $identifier = $customerData->getCustomAttribute($attribute)->getValue();

        try {
            if ($this->restHelper->getPrivateKey()) {
                $requestData = ['paymentMethodToken' => $identifier];

                // Perform REST request to cancel identifier.
                $client = new \Lyranetwork\Payzen\Model\Api\PayzenRest(
                    $this->dataHelper->getCommonConfigData('rest_url'),
                    $this->dataHelper->getCommonConfigData('site_id'),
                    $this->restHelper->getPrivateKey()
                );

                $cancelIdentifierResponse = $client->post('V4/Token/Cancel', json_encode($requestData));
                $this->restHelper->checkResult($cancelIdentifierResponse);
            } else {
                // Client has not configured private key in module backend.
                $this->dataHelper->log("Identifier for customer {$customer->getEmail()} cannot be deleted on gateway: private key is not configured. Let's just delete it from Magento.");
            }

            // Delete identifier from Magento.
            $this->deleteIdentifierAttribute($customer, $attribute, $maskedAttribute);

            return true;
        } catch (\Exception $e) {
            $invalidIdentCodes = ['PSP_030', 'PSP_031', 'PSP_561', 'PSP_607'];

            if (in_array($e->getCode(), $invalidIdentCodes)) {
                // The identifier is invalid or doesn't exist.
                $this->dataHelper->log(
                    "Identifier for customer {$customer->getEmail()} is invalid or doesn't exist. Let's delete it from Magento",
                    \Psr\Log\LogLevel::WARNING
                );

                // Delete identifier from Magento.
                $this->deleteIdentifierAttribute($customer, $attribute, $maskedAttribute);

                return true;
            } else {
                $this->dataHelper->log(
                    "Identifier for customer {$customer->getEmail()} couldn't be deleted on gateway. Error occurred: {$e->getMessage()}",
                    \Psr\Log\LogLevel::ERROR
                );

                return false;
            }
        }
    }

    private function deleteIdentifierAttribute($customer, $attribute, $maskedAttribute)
    {
        $customerData = $customer->getDataModel();
        $customerData->setId($customer->getId());
        $customerData->setCustomAttribute($attribute, null);
        $customerData->setCustomAttribute($maskedAttribute, null);
        $customer->updateData($customerData);

        $customerResource = $this->customerResourceFactory->create();
        $customerResource->saveAttribute($customer, $attribute);
        $customerResource->saveAttribute($customer, $maskedAttribute);

        $this->dataHelper->log("Identifier for customer {$customer->getEmail()} successfully deleted.");
    }

    private function maskPan($response)
    {
        $number = $response->get('card_number');
        $masked = '';

        $matches = [];
        if (preg_match('#^([A-Z]{2}[0-9]{2}[A-Z0-9]{10,30})(_[A-Z0-9]{8,11})?$#i', $number, $matches)) {
            // IBAN(_BIC).
            $masked .= isset($matches[2]) ? str_replace('_', '', $matches[2]) . ' / ' : ''; // BIC.

            $iban = $matches[1];
            $masked .= substr($iban, 0, 4) . str_repeat('X', strlen($iban) - 8) . substr($iban, -4);
        } elseif (strlen($number) > 4) {
            $masked .= str_repeat('X', strlen($number) - 4) . substr($number, -4);

            if ($response->get('expiry_month') && $response->get('expiry_year')) {
                // Format card expiration data.
                $masked .= ' - ';
                $masked .= str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT);
                $masked .= '/';
                $masked .= $response->get('expiry_year');
            }
        }

        return $response->get('card_brand') . '|' . $masked;
    }

    public function createInvoice(\Magento\Sales\Model\Order $order)
    {
        // Flag that is true if automatically create invoice.
        $autoCapture = $this->dataHelper->getCommonConfigData('capture_auto', $order->getStore()->getId());

        if (! $autoCapture || ($order->getStatus() !== 'processing') || ! $order->canInvoice()) {
            // Creating invoice not allowed.
            $this->dataHelper->log("Creating invoice not allowed for order #{$order->getIncrementId()}.");
            return;
        }

        // Check if an invoice already exists for this order.
        if ($order->hasInvoices()) {
            $alreadyInvoiced = false;
            $transId = $order->getPayment()->getLastTransId();

            $invoices = $order->getInvoiceCollection();
            foreach ($invoices as $invoice) {
                if ($invoice->getTransactionId() === $transId) {
                    $alreadyInvoiced = true;
                    break;
                }
            }

            if ($alreadyInvoiced) {
                $this->dataHelper->log("Invoice already exists for order #{$order->getIncrementId()} with transaction ID #{$transId}.");
                return;
            }
        }

        $this->dataHelper->log("Creating invoice for order #{$order->getIncrementId()}.");

        // Convert order to invoice.
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $invoice->setTransactionId($order->getPayment()->getLastTransId());
        $invoice->register()->save();
        $order->addRelatedObject($invoice);

        // Add history entry.
        $order->addStatusHistoryComment(__('Invoice %1 was created.', $invoice->getIncrementId()));

        $this->dataHelper->log("Invoice created for order #{$order->getIncrementId()}.");
    }

    /**
     * Cancel order.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Lyranetwork\Payzen\Model\Api\PayzenResponse $response
     */
    public function cancelOrder(
        \Magento\Sales\Model\Order $order,
        \Lyranetwork\Payzen\Model\Api\PayzenResponse $response
    ) {
        $this->dataHelper->log("Canceling order #{$order->getIncrementId()}.");

        $order->registerCancellation($response->get('error_message') ?: $response->getMessage());

        // Save gateway responses.
        $this->updatePaymentInfo($order, $response);

        $this->dataHelper->log("Saving cancelled order #{$order->getIncrementId()}.");
        $order->save();
        $this->dataHelper->log("Order #{$order->getIncrementId()} has been cancelled.");

        $this->eventManager->dispatch('order_cancel_after', [
            'order' => $order
        ]);
    }

    /**
     * Prepare transaction data and call \Magento\Sales\Model\Order\Payment::addTransaction.
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param string $type
     * @param string $transactionId
     * @param array $additionalInfo
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function addTransaction($payment, $type, $gatewayTransactionId, $additionalInfo)
    {
        $transactionId = $gatewayTransactionId;

        if ($type !== \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH) {
            $parentTxn = $this->transactionRepository->getByTransactionId(
                $gatewayTransactionId,
                $payment->getId(),
                $payment->getOrder()->getId()
            );

            if ($parentTxn && $parentTxn->getId() && ($parentTxn->getTxnType() !== $type)) {
                $transactionId = $this->transactionManager->generateTransactionId($payment, $type, $parentTxn);
                $payment->setShouldCloseParentTransaction(true);
            }
        } else {
            $payment->setIsTransactionClosed(0);
        }

        $payment->setTransactionId($transactionId);

        $payment->setTransactionAdditionalInfo(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $additionalInfo
        );

        $txnExists = $this->transactionManager->isTransactionExists(
            $payment->getTransactionId(),
            $payment->getId(),
            $payment->getOrder()->getId()
        );

        $payment->setSkipTransactionCreation(false);
        $txn = $payment->addTransaction($type, null, true);

        $msg = $txnExists ? 'Transaction %1 was updated.' : 'Transaction %1 was created.';
        $payment->getOrder()->addStatusHistoryComment(__($msg, $payment->getTransactionId()));

        return $txn;
    }

    public function isSepa($response)
    {
        return $response->get('card_brand') === 'SDD';
    }

    /**
     * Convert gateway transaction status to magento transaction type.
     *
     * @param string $payzenType
     * @return string
     */
    public function convertTransactionType($payzenType)
    {
        $type = false;

        switch ($payzenType) {
            case 'UNDER_VERIFICATION':
            case 'INITIAL':
            case 'WAITING_AUTHORISATION_TO_VALIDATE':
            case 'WAITING_AUTHORISATION':
            case 'AUTHORISED_TO_VALIDATE':
            case 'AUTHORISED':
            case 'CAPTURE_FAILED':
                $type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                break;

            case 'CAPTURED':
                $type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                break;

            case 'REFUSED':
            case 'EXPIRED':
            case 'CANCELLED':
            case 'NOT_CREATED':
            case 'ABANDONED':
            default:
                $type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID;
                break;
        }

        return $type;
    }

    public function getNextTransStatus($firstStatus)
    {
        switch ($firstStatus) {
            case 'AUTHORISED_TO_VALIDATE':
                return 'WAITING_AUTHORISATION_TO_VALIDATE';

            case 'AUTHORISED':
                return 'WAITING_AUTHORISATION';

            default:
                return $firstStatus;
        }
    }
}
