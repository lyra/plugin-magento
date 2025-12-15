<?php

/**
 * Copyright © Lyra Network and contributors.
 * This file is part of PayZen plugin for WooCommerce. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @author    Geoffrey Crofte, Alsacréations (https://www.alsacreations.fr/)
 * @copyright Lyra Network and contributors
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
 */
namespace Lyranetwork\Payzen\Helper;

use Lyranetwork\Payzen\Model\Api\Form\Api as PayzenApi;
use Lyranetwork\Payzen\Model\Api\Refund\WsException as WsException;

class Refund implements \Lyranetwork\Payzen\Model\Api\Refund\Processor
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Lyranetwork\Payzen\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * @var \Lyranetwork\Payzen\Helper\Rest
     */
    protected $restHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Sales\Model\Order\payment
     */
    protected $payment;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     * @param \Lyranetwork\Payzen\Helper\Rest $restHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Payment $paymentHelper,
        \Lyranetwork\Payzen\Helper\Rest $restHelper,
        \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory $collectionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->dataHelper = $dataHelper;
        $this->paymentHelper = $paymentHelper;
        $this->restHelper = $restHelper;
        $this->collectionFactory = $collectionFactory;
        $this->messageManager = $messageManager;
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;
        return $this;
    }

    /**
     * Action to do in case of error during refund process.
     *
     */
    public function doOnError($errorCode, $message)
    {
        if ($errorCode === 'PSP_100') {
            // Merchant has not subscribed to REST WS option, let Magento refund payment offline.
            $notice = __('You are not authorized to do this action online. Please, do not forget to update payment in PayZen Back Office.');
            $this->messageManager->addWarningMessage($notice);
        } else {
            $this->messageManager->addWarningMessage($message);
        }
    }

    /**
     * Action to do after successful refund process.
     *
     */
    public function doOnSuccess($operationResponse, $operationType)
    {
        $orderId = $operationResponse['orderDetails']['orderId'];
        $order = $this->dataHelper->getOrderByIncrementId($orderId);

        if ($operationType == 'refund') { // Actual refund.
            $this->createRefundTransaction($this->payment, $operationResponse);
        } elseif ($operationType == 'cancel') { // Cancellation.
            $order->cancel();
        } elseif ($operationType == 'frac_update') { // Split payment.
            $currency = PayzenApi::findCurrencyByAlphaCode($order->getOrderCurrencyCode());

            $transRefundAmount = 0;
            $transRefundedAmount = 0;
            if ($currency) {
                if (isset($operationResponse['amount']) && $operationResponse['amount']) {
                    $transRefundAmount = round($currency->convertAmountToFloat($operationResponse['amount']), $currency->getDecimals());
                }

                if (isset($operationResponse['refundedAmount']) && $operationResponse['refundedAmount']) {
                    $transRefundedAmount = round($currency->convertAmountToFloat($operationResponse['refundedAmount']), $currency->getDecimals());
                }
            }

            $orderRefundedAmount = ($order->getTotalRefunded()) ? $order->getTotalRefunded() : 0;
            if ($transRefundedAmount > $orderRefundedAmount) {
                // The amount refund situation is not up to date.
                $refundTransactions = $this->getOrderRefundTransactions($orderId);
                $boRefundedTransactions = $operationResponse['refundTransactions'];
                $leftOutTransactions = [];
                $leftOutTransactionsAmount = [];
                $totalLeftOutAmount = 0;

                // Check the left out transactions and create them.
                foreach ($boRefundedTransactions as $key => $trans) {
                    if ($trans['uuid'] && ! array_key_exists($trans['uuid'], $refundTransactions)) {
                        $leftOutTransactions[$trans['uuid']] = $trans;
                        $transAmount = round($currency->convertAmountToFloat($trans['amount']), $currency->getDecimals());
                        $leftOutTransactionsAmount[$trans['uuid']] = $transAmount;
                        $totalLeftOutAmount += $transAmount;
                    }
                }

                if (! empty($leftOutTransactionsAmount)) {
                    if ($transRefundAmount == $totalLeftOutAmount) {
                        // One or more missing transactions in Magento, let the refund go through and create necessary refund transaction.
                        foreach ($leftOutTransactionsAmount as $key => $trans) {
                            $transactionInfo = $leftOutTransactions[$key];
                            $this->createRefundTransaction($this->payment, $transactionInfo);
                        }
                    } else {
                        if (($trsUuid = array_search($transRefundAmount, $leftOutTransactionsAmount)) !== false) {
                            // The refund amount equals one of the unregistered transactions, let the refund go through and create necessary refund transaction.
                            $transactionInfo = $leftOutTransactions[$trsUuid];
                            $this->createRefundTransaction($this->payment, $transactionInfo);
                        } else {
                            // The amount of refund requested doesn't correspond to any refund transaction in merchant BO.
                            throw new \Exception(__('The requested refund amount does not correspond to any refund transaction in the PayZen Back Office.'));
                        }
                    }
                } else {
                    // All transactions are created in Magento, it's just the order info that is not up to date, the merchant should do an offline refund just like a normal refund.
                    throw new \Exception(__('Refund of split payment is already done in PayZen Back Office. Please, consider making an offline refund in Magento.'));
                }
            } else {
                // Order already up to date, it's a new request of refund.
                throw new \Exception(sprintf(__('Refund of split payment is not supported. Please, consider making necessary changes in %1$s Back Office.'), 'PayZen'));
            }
        }
    }

    private function getOrderRefundTransactions($orderId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addOrderIdFilter($orderId);
        $collection->load();
        $refundTransactionsUuid = [];
        if ($collection && count($collection) != 0) {
            foreach ($collection as $item) {
                if ($item->getTxnType() == \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND) {
                    $additionalInfo = $item->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);
                    if ($additionalInfo && isset($additionalInfo['Transaction UUID'])) {
                        $refundTransactionsUuid[$additionalInfo['Transaction UUID']] = $additionalInfo;
                    }
                }
            }
        }

        return $refundTransactionsUuid;
    }

    private function createRefundTransaction($payment, $refundResponse)
    {
        $response = $this->restHelper->convertRestResult($refundResponse, true);

        // Save transaction details to sales_payment_transaction.
        $transactionId = $response['vads_trans_id'] . '-' . $response['vads_sequence_number'];

        $expiry = '';
        if ($response['vads_expiry_month'] && $response['vads_expiry_year']) {
            $expiry = str_pad($response['vads_expiry_month'], 2, '0', STR_PAD_LEFT) . ' / ' . $response['vads_expiry_year'];
        }

        // Save paid amount.
        $currency = PayzenApi::findCurrencyByNumCode($response['vads_currency']);
        $amount = round($currency->convertAmountToFloat($response['vads_amount']), $currency->getDecimals());

        $amountDetail = $amount . ' ' . $currency->getAlpha3();

        if (isset($response['vads_effective_currency']) &&
            ($response['vads_currency'] !== $response['vads_effective_currency'])) {
            $effectiveCurrency = PayzenApi::findCurrencyByNumCode($response['vads_effective_currency']);

            $effectiveAmount = round(
                $effectiveCurrency->convertAmountToFloat($response['vads_effective_amount']),
                $effectiveCurrency->getDecimals()
            );

            $amountDetail = $effectiveAmount . ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
        }

        $additionalInfo = [
            'Transaction Type' => 'CREDIT',
            'Amount' => $amountDetail,
            'Transaction ID' => $transactionId,
            'Transaction UUID' => $response['vads_trans_uuid'],
            'Transaction Status' => $response['vads_trans_status'],
            'Means of payment' => $response['vads_wallet'] ? $response['vads_wallet'] : $response['vads_card_brand'],
            'Card Number' => $response['vads_card_number'],
            'Expiration Date' => $expiry
        ];

        $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND;
        $this->paymentHelper->addTransaction($payment, $transactionType, $transactionId, $additionalInfo);
    }

    /**
     * Action to do after failed refund process.
     *
     */
    public function doOnFailure($errorCode, $message)
    {
        throw new WsException($message, $errorCode);
    }

    /**
     * Translate given message.
     *
     */
    public function translate($message)
    {
        return __($message);
    }

    public function log($message, $level = \Psr\Log\LogLevel::INFO)
    {
        $this->dataHelper->log($message, $level);
    }
}
