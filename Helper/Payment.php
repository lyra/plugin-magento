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
namespace Lyranetwork\Payzen\Helper;

use Lyranetwork\Payzen\Model\Api\PayzenApi;

class Payment
{

    // key to save if payment is by identifier
    const IDENTIFIER = 'payzen_identifier';

    // key to save if card data register is on
    const CC_REGISTER = 'payzen_cc_register';

    // key to save choosen multi option
    const MULTI_OPTION = 'payzen_multi_option';

    // key to save choosen Oney option
    const ONEY_OPTION = 'payzen_oney_option';

    // key to save risk control results
    const RISK_CONTROL = 'payzen_risk_control';

    // key to save risk assessment results
    const RISK_ASSESSMENT = 'payzen_risk_assessment';

    // key to save risk assessment results
    const ALL_RESULTS = 'payzen_all_results';

    const TRANS_UUID = 'payzen_trans_uuid';

    const ONECLICK_LOCATION_CART = 'CART';

    const ONECLICK_LOCATION_PRODUCT = 'PRODUCT';

    const ONECLICK_LOCATION_BOTH = 'BOTH';

    /**
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     *
     * @var \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface
     */
    protected $transactionManager;

    /**
     *
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     *
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     *
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

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
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface $transactionManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface $transactionManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->eventManager = $eventManager;
        $this->transactionRepository = $transactionRepository;
        $this->transactionManager = $transactionManager;
        $this->customerFactory = $customerFactory;
        $this->orderSender = $orderSender;
        $this->dataHelper = $dataHelper;
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

        $this->dataHelper->log("Saving payment for order #{$order->getId()}.");

        // update authorized amount
        $order->getPayment()->setAmountAuthorized($order->getTotalDue());
        $order->getPayment()->setBaseAmountAuthorized($order->getBaseTotalDue());

        // retrieve new order state and status
        $stateObject = $this->nextOrderState($order, $response);

        $this->dataHelper->log("Order #{$order->getId()}, new state : {$stateObject->getState()}," .
                 " new status : {$stateObject->getStatus()}.");
        $order->setState($stateObject->getState())
            ->setStatus($stateObject->getStatus())
            ->addStatusHistoryComment($response->getMessage());

        // save platform responses
        $this->updatePaymentInfo($order, $response);

        // try to save PayZen identifier if any
        $this->saveIdentifier($order, $response);

        // try to create invoice
        $this->createInvoice($order);

        $this->dataHelper->log("Saving confirmed order #{$order->getId()} and sending e-mail if not disabled.");
        $order->save();

        if ($order->getSendEmail() === null /* not set */ || $order->getSendEmail() /* set to true */) {
            $this->orderSender->send($order);
        }
    }

    /**
     * Get new order state and status according to PayZen response.
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
            $newStatus = $this->dataHelper->getCommonConfigData(
                'registered_order_status',
                $order->getStore()->getId()
            );

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
        // set common payment information
        $order->getPayment()
            ->setCcTransId($response->get('trans_id'))
            ->setCcType($response->get('card_brand'))
            ->setCcStatus($response->getResult())
            ->setCcStatusDescription($response->getMessage())
            ->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::ALL_RESULTS, serialize($response->getAllResults()))
            ->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::TRANS_UUID, $response->get('trans_uuid'));

        if ($response->isCancelledPayment()) {
            // no more data to save
            return;
        }

        // save risk control result if any
        $riskControl = $response->getRiskControl();
        if (! empty($riskControl)) {
            $order->getPayment()->setAdditionalInformation(self::RISK_CONTROL, serialize($riskControl));
        }

        // save risk assessment result if any
        $riskAssessment = $response->getRiskAssessment();
        if (! empty($riskAssessment)) {
            $order->getPayment()->setAdditionalInformation(self::RISK_ASSESSMENT, serialize($riskAssessment));
        }

        // set is_fraud_detected flag
        $order->getPayment()->setIsFraudDetected($response->isSuspectedFraud());

        if ($response->get('card_brand') == 'MULTI') { // multi brand
            $data = json_decode($response->get('payment_seq'));
            $transactions = $data->{'transactions'};

            $currency = PayzenApi::findCurrencyByNumCode($response->get('currency'));

            // save transaction details to sales_payment_transaction
            foreach ($transactions as $trs) {
                // save transaction details to sales_payment_transaction
                $expiry = '';
                if (! empty($trs->{'expiry_month'}) && ! empty($trs->{'expiry_year'})) {
                    $expiry = str_pad($trs->{'expiry_month'}, 2, '0', STR_PAD_LEFT) . ' / ' . $trs->{'expiry_year'};
                }

                $transactionId = $response->get('trans_id') . '-' . $trs->{'sequence_number'};

                // save paid amount
                $amount = round($currency->convertAmountToFloat($trs->{'amount'}), $currency->getDecimals());

                $amountDetail = $amount . ' ' . $currency->getAlpha3();

                $additionalInfo = [
                    'Transaction Type' => $trs->{'operation_type'},
                    'Amount' => $amountDetail,
                    'Transaction ID' => $transactionId,
                    'Extra Transaction ID' => property_exists($trs, 'ext_trans_id') && isset($trs->{'ext_trans_id'}) ? $trs->{'ext_trans_id'} : '',
                    'Transaction Status' => $trs->{'trans_status'},
                    'Payment Mean' => $trs->{'card_brand'},
                    'Card Number' => $trs->{'card_number'},
                    'Expiration Date' => $expiry
                ];

                $transactionType = $this->convertTransactionType($trs->{'trans_status'});

                $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
            }
        } else {
            // 3-DS authentication result
            $threedsCavv = '';
            if ($response->get('threeds_status') === 'Y') {
                $threedsCavv = $response->get('threeds_cavv');
            }

            // save payment infos to sales_flat_order_payment
            $order->getPayment()
                ->setCcExpMonth($response->get('expiry_month'))
                ->setCcExpYear($response->get('expiry_year'))
                ->setCcNumberEnc($response->get('card_number'))
                ->setCcSecureVerify($threedsCavv);

            // save transaction details to sales_payment_transaction
            $expiry = '';
            if ($response->get('expiry_month') && $response->get('expiry_year')) {
                $expiry = str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT) . ' / ' .
                     $response->get('expiry_year');
            }

            // Magento transaction type
            $transactionType = $this->convertTransactionType($response->getTransStatus());

            $timestamp = strtotime($response->get('presentation_date') . ' UTC');
            $date = new \DateTime();

            // total payment amount
            $currency = PayzenApi::findCurrencyByNumCode($response->get('currency'));
            $totalAmount = (int) $response->get('amount');

            // get choosen payment option if any
            $option = @unserialize($order->getPayment()->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION));

            if ($response->get('sequence_number') == 1 && (stripos($order->getPayment()->getMethod(), 'payzen_multi') === 0)
                && is_array($option) && !empty($option)) {
                $count = (int) $option['count'];

                if (isset($option['first']) && $option['first']) {
                    $firstAmount = round($totalAmount * $option['first'] / 100);
                } else {
                    $firstAmount = round($totalAmount / $count);
                }

                // installment amount, double cast to avoid rounding
                $installmentAmount = (int) (string) (($totalAmount - $firstAmount) / ($count - 1));

                for ($i = 1; $i <= $count; $i++) {
                    $transactionId = $response->get('trans_id') . '-' . $i;

                    $delay = (int) $option['period'] * ($i - 1);
                    $date->setTimestamp(strtotime("+$delay days", $timestamp));

                    switch (true) {
                        case ($i == 1): // first transaction
                            $amount = $firstAmount;
                            break;

                        case ($i == $count): // last transaction
                            $amount = $totalAmount - $firstAmount - $installmentAmount * ($i - 2);
                            break;

                        default: // others
                            $amount = $installmentAmount;
                            break;
                    }

                    $floatAmount = round($currency->convertAmountToFloat($amount), $currency->getDecimals());
                    $amountDetail = $floatAmount . ' ' . $currency->getAlpha3();

                    if (($rate = $response->get('change_rate')) && $response->get('effective_currency') &&
                        ($response->get('currency') !== $response->get('effective_currency'))) {
                        // effective amount
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
                        'Transaction Status' => ($i == 1) ? $response->getTransStatus() : $this->getNextTransStatus($response->getTransStatus()),
                        'Payment Mean' => $response->get('card_brand'),
                        'Credit Card Number' => $response->get('card_number'),
                        'Expiration Date' => $expiry,
                        '3-DS Certificate' => $threedsCavv
                    ];

                    $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
                }
            } else {
                // save transaction details to sales_payment_transaction
                $transactionId = $response->get('trans_id') . '-' . $response->get('sequence_number');

                $floatAmount = round($currency->convertAmountToFloat($totalAmount), $currency->getDecimals());
                $amountDetail = $floatAmount . ' ' . $currency->getAlpha3();

                if ($response->get('effective_currency') &&
                    ($response->get('currency') !== $response->get('effective_currency'))) {
                    // effective amount
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
                    'Transaction Status' => $response->getTransStatus(),
                    'Payment Mean' => $response->get('card_brand'),
                    'Credit Card Number' => $response->get('card_number'),
                    'Expiration Date' => $expiry,
                    '3-DS Certificate' => $threedsCavv
                ];

                $this->addTransaction($order->getPayment(), $transactionType, $transactionId, $additionalInfo);
            }
        }

        // skip automatic transaction creation
        $order->getPayment()
            ->setTransactionId(null)
            ->setSkipTransactionCreation(true);
    }

    public function saveIdentifier(
        \Magento\Sales\Model\Order $order,
        \Lyranetwork\Payzen\Model\Api\PayzenResponse $response
    ) {

        if (! $order->getCustomerId()) {
            return;
        }

        if ($response->get('identifier') && (
            $response->get('identifier_status') == 'CREATED' /* page_action REGISTER_PAY or ASK_REGISTER_PAY */ ||
            $response->get('identifier_status') == 'UPDATED' /* page_action REGISTER_UPDATE_PAY */
        )) {
            $customer = $this->customerFactory->create()->load($order->getCustomerId());

            $this->dataHelper->log("Identifier for customer #{$customer->getId()} successfully created" .
                 ' or updated on payment platform. Let us save it to customer entity.');

            $customer->setData('payzen_identifier', $response->get('identifier'));
            $customer->save();

            $this->dataHelper->log("Identifier for customer #{$customer->getId()}" .
                ' successfully saved to customer entity.');
        }
    }

    public function createInvoice(\Magento\Sales\Model\Order $order)
    {
        // flag that is true if automatically create invoice
        $autoCapture = $this->dataHelper->getCommonConfigData('capture_auto', $order->getStore()->getId());

        if (! $autoCapture || $order->getStatus() != 'processing' || ! $order->canInvoice()) {
            // creating invoice not allowed
            return;
        }

        $this->dataHelper->log("Creating invoice for order #{$order->getId()}.");

        // convert order to invoice
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $invoice->setTransactionId($order->getPayment()->getLastTransId());
        $invoice->register()->save();
        $order->addRelatedObject($invoice);

        // add history entry
        $order->addStatusHistoryComment(__('Invoice %1 was created.', $invoice->getIncrementId()));
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

        $this->dataHelper->log("Canceling order #{$order->getId()}.");

        $order->registerCancellation($response->getMessage());

        // save platform responses
        $this->updatePaymentInfo($order, $response);
        $order->save();

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
     * @param string $parentTransactionId
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function addTransaction($payment, $type, $transactionId, $additionalInfo)
    {
        $parentTxn = $this->transactionRepository->getByTransactionId(
            $transactionId,
            $payment->getId(),
            $payment->getOrder()->getId()
        );

        if ($parentTxn && $parentTxn->getId() && $parentTxn->getTxnType() != $type) {
            $payment->setTransactionId($this->transactionManager->generateTransactionId($payment, $type, $parentTxn));
            $payment->setShouldCloseParentTransaction(true);
        } else {
            $payment->setTransactionId($transactionId);
        }

        $payment->setTransactionAdditionalInfo(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $additionalInfo
        );

        if ($type == \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH) {
            $payment->setIsTransactionClosed(0);
        }

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

    /**
     * Convert PayZen transaction status to magento transaction type.
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
