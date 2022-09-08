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

use Lyranetwork\Payzen\Model\Api\Refund\Processor as RefundProcessor;
use Lyranetwork\Payzen\Model\Api\Form\Api as PayzenApi;

class Refund implements RefundProcessor
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
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\payment
     */
    protected $payment;

    /**
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     * @param \Lyranetwork\Payzen\Helper\Rest $restHelper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Payment $paymentHelper,
        \Lyranetwork\Payzen\Helper\Rest $restHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->dataHelper = $dataHelper;
        $this->paymentHelper = $paymentHelper;
        $this->restHelper = $restHelper;
        $this->orderFactory = $orderFactory;
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
        $this->log("Refund payment error with code {$errorCode}: {$message}.", \Psr\Log\LogLevel::ERROR);
    }

    /**
     * Action to do after successful refund process.
     *
     */
    public function doOnSuccess($operationResponse, $operationType)
    {
        $order = $this->orderFactory->create();
        $orderId = $operationResponse['orderDetails']['orderId'];
        $order->loadByIncrementId($orderId);

        if ($operationType == 'refund') { // Actual refund.
            $this->createRefundTransaction($this->payment, $operationResponse);
        } elseif ($operationType == 'cancel') { // Cancellation.
            $order->cancel();
        }
    }

    private function createRefundTransaction($payment, $refundResponse)
    {
        $response = $this->restHelper->convertRestResult($refundResponse, true);

        // Save transaction details to sales_payment_transaction.
        $transactionId = $response['vads_trans_id'] . '-' . $response['vads_sequence_number'];

        $expiry = '';
        if ($response['vads_expiry_month'] && $response['vads_expiry_year']) {
            $expiry = str_pad($response['vads_expiry_month'], 2, '0', STR_PAD_LEFT) . ' / ' .
                $response['vads_expiry_year'];
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
            'Means of payment' => $response['vads_card_brand'],
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
        $this->doOnError($errorCode, $message);
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
