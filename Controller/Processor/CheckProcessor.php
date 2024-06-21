<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Processor;

use \Lyranetwork\Payzen\Helper\Payment;
use \Lyranetwork\Payzen\Model\Api\Form\Api as PayzenApi;
use \Lyranetwork\Payzen\Model\ResponseException;

class CheckProcessor
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $emulation;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Lyranetwork\Payzen\Model\Api\Form\ResponseFactory
     */
    protected $payzenResponseFactory;


    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $creditmemoFactory;


    /**
     * @var \Magento\Sales\Model\Service\CreditmemoService
     */
    protected $creditmemoService;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\App\Emulation $emulation
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param  \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Lyranetwork\Payzen\Model\Api\Form\ResponseFactory $payzenResponseFactory
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
     * @param \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $emulation,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
         \Lyranetwork\Payzen\Helper\Payment $paymentHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Lyranetwork\Payzen\Model\Api\Form\ResponseFactory $payzenResponseFactory,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
    ) {
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->dataHelper = $dataHelper;
        $this->paymentHelper = $paymentHelper;
        $this->orderFactory = $orderFactory;
        $this->payzenResponseFactory = $payzenResponseFactory;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
    }

    public function execute(
        \Magento\Sales\Model\Order $order,
        \Lyranetwork\Payzen\Model\Api\Form\Response $response
    ) {
        $this->dataHelper->log("Request authenticated for order #{$order->getIncrementId()}.");

        $reviewStatuses = [
            'payment_review',
            'payzen_to_validate',
            'fraud',
            'payzen_pending_transfer'
        ];

        if ($order->getStatus() === 'pending_payment' || in_array($order->getStatus(), $reviewStatuses)) {
            // Order waiting for payment.
            $this->dataHelper->log("Order #{$order->getIncrementId()} is waiting payment update.");
            $this->dataHelper->log("Payment result for order #{$order->getIncrementId()}: " . ($response->get('error_message') ?: $response->getLogMessage()));

            if ($response->isAcceptedPayment()) {
                $this->dataHelper->log("Payment for order #{$order->getIncrementId()} has been confirmed by notification URL.");

                $stateObject = $this->paymentHelper->nextOrderState($order, $response);
                if ($order->getStatus() === $stateObject->getStatus()) {
                    // Payment status is unchanged display notification url confirmation message.
                    return 'payment_ok_already_done';
                } else {
                    // Save order and optionally create invoice.
                    $this->paymentHelper->registerOrder($order, $response);

                    // Display notification URL confirmation message.
                    return 'payment_ok';
                }
            } else {
                $this->dataHelper->log("Payment for order #{$order->getIncrementId()} has been invalidated by notification URL.");

                // Cancel order.
                $this->paymentHelper->cancelOrder($order, $response);

                // Display notification URL failure message.
                return 'payment_ko';
            }
        } else {
            // Payment already processed.
            $acceptedStatus = $this->dataHelper->getCommonConfigData('registered_order_status', $order->getStore()->getId());
            $successStatuses = [
                $acceptedStatus,
                'complete' // Case of virtual orders.
            ];

            if ($order->isCanceled() && $response->isAcceptedPayment() && $response->getExtInfo('update_order')) {
                $this->paymentHelper->unCancelOrder($order, $response);

                // Display notification URL confirmation message.
                return 'payment_ok';
            } elseif ($response->isAcceptedPayment() && in_array($order->getStatus(), $successStatuses)) {
                $this->dataHelper->log("Order #{$order->getIncrementId()} is confirmed.");

                if ($response->get('operation_type') === 'CREDIT') {
                    // Save paid amount.
                    $currency = PayzenApi::findCurrencyByNumCode($response->get('currency'));
                    $amount = round(
                        $currency->convertAmountToFloat($response->get('amount')),
                        $currency->getDecimals()
                    );

                    // Check if display currency is used for refund, otherwise use amount in base currency.
                    if ($order->getOrderCurrencyCode() !== $currency->getAlpha3()) {
                        $totalOrderAmount = $order->getBaseGrandTotal();
                    } else {
                        $totalOrderAmount = $order->getGrandTotal();
                    }

                    $orderAmount = round(
                        $totalOrderAmount,
                        $currency->getDecimals()
                    );

                    if ($amount === $orderAmount) { // Total refund: we can create a credit memo in Magento.
                        try {
                            $creditMemo = $this->creditmemoFactory->createByOrder($order);
                            $this->creditmemoService->refund($creditMemo, true);
                            $this->dataHelper->log("Credit memo successfully created for order #{$order->getIncrementId()}.");
                        } catch (\Exception $e) {
                            $this->dataHelper->log(
                                "Error while creating credit memo for order #{$order->getIncrementId()}: {$e->getMessage()}.",
                                 \Psr\Log\LogLevel::ERROR
                           );
                        }
                    }

                    $expiry = '';
                    if ($response->get('expiry_month') && $response->get('expiry_year')) {
                        $expiry = str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT) . ' / ' .
                             $response->get('expiry_year');
                    }

                    $transactionId = $response->get('trans_id') . '-' . $response->get('sequence_number');
                    $amountDetail = $amount . ' ' . $currency->getAlpha3();

                    if ($response->get('effective_currency') &&
                        ($response->get('currency') !== $response->get('effective_currency'))) {
                        $effectiveCurrency = PayzenApi::findCurrencyByNumCode($response->get('effective_currency'));

                        $effectiveAmount = round(
                            $effectiveCurrency->convertAmountToFloat($response->get('effective_amount')),
                            $effectiveCurrency->getDecimals()
                        );

                        $amountDetail = $effectiveAmount . ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
                    }

                    $additionalInfo = [
                        'Transaction Type' => 'CREDIT',
                        'Amount' => $amountDetail,
                        'Transaction ID' => $transactionId,
                        'Transaction UUID' => $response->get('trans_uuid'),
                        'Transaction Status' => $response->get('trans_status'),
                        'Means of payment' => $response->get('card_brand'),
                        'Card Number' => $response->get('card_number'),
                        'Expiration Date' => $expiry,
                        '3DS Certificate' => ''
                    ];

                    $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND;

                    $this->paymentHelper->addTransaction(
                        $order->getPayment(),
                        $transactionType,
                        $transactionId,
                        $additionalInfo
                    );
                } else {
                    // Update transaction info.
                    $this->paymentHelper->updatePaymentInfo($order, $response);
                }

                $this->dataHelper->log("Saving confirmed order #{$order->getIncrementId()}.");
                $order->save();
                $this->dataHelper->log("Confirmed order #{$order->getIncrementId()} has been saved.");

                return 'payment_ok_already_done';
            } elseif ($order->isCanceled() && ! $response->isAcceptedPayment()) {
                $this->dataHelper->log("Order #{$order->getIncrementId()} cancellation is confirmed.");
                return 'payment_ko_already_done';
            } else {
                // Error case, the payment result and the order status do not match.
                $msg = "Invalid payment result received for already saved order #{$order->getIncrementId()}.";
                $msg .= " Payment result: {$response->getTransStatus()}, order status : {$order->getStatus()}.";
                $this->dataHelper->log($msg, \Psr\Log\LogLevel::ERROR);

                return 'payment_ko_on_order_ok';
            }
        }
    }

    public function prepareResponse($params)
    {
        // Loading order.
        $order = $this->findOrder($params);

        // Get store id from order.
        $storeId = $order->getStore()->getId();

        // Init app with correct store environment. No need to stop emulation on an IPN call.
        $this->emulation->startEnvironmentEmulation($storeId);

        // Load API response.
        $response = $this->payzenResponseFactory->create(
            [
                'params' => $params,
                'ctx_mode' => $this->dataHelper->getCommonConfigData('ctx_mode', $storeId),
                'key_test' => $this->dataHelper->getCommonConfigData('key_test', $storeId),
                'key_prod' => $this->dataHelper->getCommonConfigData('key_prod', $storeId),
                'algo' => $this->dataHelper->getCommonConfigData('sign_algo', $storeId)
            ]
        );

        if (! $response->isAuthentified()) {
            // Authentification failed.
            $this->dataHelper->log(
                "{$this->dataHelper->getIpAddress()} tries to access payzen/payment/check page without valid signature with parameters: " . json_encode($params),
                \Psr\Log\LogLevel::ERROR
            );

            $this->dataHelper->log(
                'Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.',
                \Psr\Log\LogLevel::ERROR
            );

            throw new ResponseException($response->getOutputForGateway('auth_fail'));
        }

        return [
            'response' => $response,
            'order' => $order
        ];
    }

    private function findOrder($params)
    {
        // Load order.
        $orderId = key_exists('vads_order_id', $params) ? $params['vads_order_id'] : null;
        if (! $orderId) {
            $this->dataHelper->log('Order ID is empty. Content: ' . json_encode($params), \Psr\Log\LogLevel::ERROR);
            throw new ResponseException('<span style="display:none">KO-Invalid IPN request received.' . "\n" . '</span>');
        }

        // Loading order.
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($orderId);

        if (! $order->getId()) {
            $this->dataHelper->log("Order not found with ID #{$orderId}.", \Psr\Log\LogLevel::ERROR);
            throw new ResponseException('<span style="display:none">KO-Order not found.' . "\n" . '</span>');
        }

        return $order;
    }

    public function getDataHelper()
    {
        return $this->dataHelper;
    }

    public function getStoreManager()
    {
        return $this->storeManager;
    }

    public function getOrderFactory()
    {
        return $this->orderFactory;
    }

    public function getPayzenResponseFactory()
    {
        return $this->payzenResponseFactory;
    }
}
