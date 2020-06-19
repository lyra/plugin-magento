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

use Lyranetwork\Payzen\Helper\Payment;
use Lyranetwork\Payzen\Model\ResponseException;

class ResponseProcessor
{
    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Payment
     */
    protected $paymentHelper;

    /**
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     *
     * @var \Lyranetwork\Payzen\Model\Api\PayzenResponseFactory
     */
    protected $payzenResponseFactory;

    /**
     *
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Lyranetwork\Payzen\Model\Api\PayzenResponseFactory $payzenResponseFactory
     */
    public function __construct(
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Payment $paymentHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Lyranetwork\Payzen\Model\Api\PayzenResponseFactory $payzenResponseFactory
    ) {
        $this->dataHelper = $dataHelper;
        $this->paymentHelper = $paymentHelper;
        $this->orderFactory = $orderFactory;
        $this->payzenResponseFactory = $payzenResponseFactory;
    }

    public function execute(
        \Magento\Sales\Model\Order $order,
        \Lyranetwork\Payzen\Model\Api\PayzenResponse $response
    ) {
        $this->dataHelper->log("Request authenticated for order #{$order->getId()}.");

        if ($order->getStatus() === 'pending_payment') {
            // Order waiting for payment.
            $this->dataHelper->log("Order #{$order->getId()} is waiting payment.");
            $this->dataHelper->log("Payment result for order #{$order->getId()}: " . $response->getLogMessage());

            if ($response->isAcceptedPayment()) {
                $this->dataHelper->log("Payment for order #{$order->getId()} has been confirmed by client return !" .
                     " This means the notification URL did not work.", \Psr\Log\LogLevel::WARNING);

                // Save order and optionally create invoice.
                $this->paymentHelper->registerOrder($order, $response);

                // Display success page.
                return [
                    'case' => Payment::SUCCESS,
                    'warn' => true // Notification URL warn in TEST mode.
                ];
            } else {
                $this->dataHelper->log("Payment for order #{$order->getId()} has failed.");

                // Cancel order.
                $this->paymentHelper->cancelOrder($order, $response);

                // Redirect to cart page.
                $case = $response->isCancelledPayment() ? Payment::CANCEL : Payment::FAILURE;
                return [
                    'case' => $case,
                    'warn' => false
                ];
            }
        } else {
            // Payment already processed.
            $this->dataHelper->log("Order #{$order->getId()} has already been processed.");

            $storeId = $this->dataHelper->getCheckoutStoreId();
            $acceptedStatus = $this->dataHelper->getCommonConfigData('registered_order_status', $storeId);
            $successStatuses = [
                $acceptedStatus,
                'complete', // Virtual orders.
                'payment_review', // Pending payments.
                'fraud', // Fraud status is taken as successful because it's just a suspicion.
                'payzen_to_validate', // Payment will be OK after manual validation.
                'payzen_pending_transfer' // For SEPA payments.
            ];

            if ($response->isAcceptedPayment() && in_array($order->getStatus(), $successStatuses)) {
                $this->dataHelper->log("Order #{$order->getId()} is confirmed.");

                return [
                    'case' => Payment::SUCCESS,
                    'warn' => false
                ];
            } elseif ($order->isCanceled() && ! $response->isAcceptedPayment()) {
                $this->dataHelper->log("Order #{$order->getId()} cancelation is confirmed.");

                $case = $response->isCancelledPayment() ? Payment::CANCEL : Payment::FAILURE;
                return [
                    'case' => $case,
                    'warn' => false
                ];
            } else {
                // Error case, the payment result and the order status do not match.
                $msg = "Invalid payment result received for already saved order #{$order->getId()}.";
                $msg .= " Payment result: {$response->getTransStatus()}, order status : {$order->getStatus()}.";

                throw new ResponseException($msg);
            }
        }
    }

    public function prepareResponse($params)
    {
        $order = $this->findOrder($params);

        $storeId = $order->getStore()->getId();

        // Load response API.
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
            $msg = "{$this->dataHelper->getIpAddress()} tries to access payzen/payment/response page without valid signature with parameters: " . json_encode($params);
            $msg .= "\n";
            $msg .= 'Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.';

            throw new ResponseException($msg);
        }

        return [
            'response' => $response,
            'order' => $order
        ];
    }

    private function findOrder($params)
    {
        $orderId = key_exists('vads_order_id', $params) ? $params['vads_order_id'] : null;
        if (! $orderId) {
            throw new ResponseException('Order ID is empty. Content: ' . json_encode($params));
        }

        // Load order.
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($orderId);
        if (! $order->getId()) {
            throw new ResponseException("Order not found with ID #{$orderId}.");
        }

        return $order;
    }

    public function getDataHelper()
    {
        return $this->dataHelper;
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
