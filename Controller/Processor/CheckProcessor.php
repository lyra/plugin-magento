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
namespace Lyranetwork\Payzen\Controller\Processor;

use \Lyranetwork\Payzen\Model\Api\PayzenApi;

class CheckProcessor
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

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
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Lyranetwork\Payzen\Model\Api\PayzenResponseFactory $payzenResponseFactory
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Payment $paymentHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Lyranetwork\Payzen\Model\Api\PayzenResponseFactory $payzenResponseFactory
    ) {
        $this->storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
        $this->paymentHelper = $paymentHelper;
        $this->orderFactory = $orderFactory;
        $this->payzenResponseFactory = $payzenResponseFactory;
    }

    public function execute(\Lyranetwork\Payzen\Api\CheckActionInterface $controller)
    {
        if (! $controller->getRequest()->isPost()) {
            return;
        }

        $post = $controller->getRequest()->getParams();

        // loading order
        $orderId = key_exists('vads_order_id', $post) ? $post['vads_order_id'] : 0;
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($orderId);

        // get store id from order
        $storeId = $order->getStore()->getId();

        // init app with correct store id
        $this->storeManager->setCurrentStore($storeId);

        // load API response
        $payzenResponse = $this->payzenResponseFactory->create(
            [
                'params' => $post,
                'ctx_mode' => $this->dataHelper->getCommonConfigData('ctx_mode', $storeId),
                'key_test' => $this->dataHelper->getCommonConfigData('key_test', $storeId),
                'key_prod' => $this->dataHelper->getCommonConfigData('key_prod', $storeId)
            ]
        );

        if (! $payzenResponse->isAuthentified()) {
            // authentification failed
            $this->dataHelper->log(
                "{$this->dataHelper->getIpAddress()} tries to access payzen/payment/check page without valid signature with parameters: " . json_encode($post),
                \Psr\Log\LogLevel::ERROR
            );

            return $controller->renderResponse($payzenResponse->getOutputForPlatform('auth_fail'));
        }

        $this->dataHelper->log("Request authenticated for order #{$order->getId()}.");

        $reviewStatuses = [
            'payment_review',
            'payzen_to_validate',
            'fraud'
        ];

        if ($order->getStatus() == 'pending_payment' || in_array($order->getStatus(), $reviewStatuses)) {
            // order waiting for payment
            $this->dataHelper->log("Order #{$order->getId()} is waiting payment update.");
            $this->dataHelper->log("Payment result for order #{$order->getId()} : " . $payzenResponse->getLogMessage());

            if ($payzenResponse->isAcceptedPayment()) {
                $this->dataHelper->log("Payment for order #{$order->getId()} has been confirmed by notification URL.");

                $stateObject = $this->paymentHelper->nextOrderState($order, $payzenResponse);
                if ($order->getStatus() == $stateObject->getStatus()) {
                    // payment status is unchanged display notification url confirmation message
                    return $controller->renderResponse($payzenResponse->getOutputForPlatform('payment_ok_already_done'));
                } else {
                    // save order and optionally create invoice
                    $this->paymentHelper->registerOrder($order, $payzenResponse);

                    // display notification url confirmation message
                    return $controller->renderResponse($payzenResponse->getOutputForPlatform('payment_ok'));
                }
            } else {
                $this->dataHelper->log("Payment for order #{$order->getId()} has been invalidated by notification URL.");

                // cancel order
                $this->paymentHelper->cancelOrder($order, $payzenResponse);

                // display notification url failure message
                return $controller->renderResponse($payzenResponse->getOutputForPlatform('payment_ko'));
            }
        } else {
            // payment already processed

            $acceptedStatus = $this->dataHelper->getCommonConfigData('registered_order_status', $storeId);
            $successStatuses = [
                $acceptedStatus,
                'complete' /* case of virtual orders */
            ];

            if ($payzenResponse->isAcceptedPayment() && in_array($order->getStatus(), $successStatuses)) {
                $this->dataHelper->log("Order #{$order->getId()} is confirmed.");

                if ($payzenResponse->get('operation_type') == 'CREDIT') {
                    // this is a refund : create credit memo ?

                    $expiry = '';
                    if ($payzenResponse->get('expiry_month') && $payzenResponse->get('expiry_year')) {
                        $expiry = str_pad($payzenResponse->get('expiry_month'), 2, '0', STR_PAD_LEFT) . ' / ' .
                             $payzenResponse->get('expiry_year');
                    }

                    $transactionId = $payzenResponse->get('trans_id') . '-' . $payzenResponse->get('sequence_number');

                    // save paid amount
                    $currency = PayzenApi::findCurrencyByNumCode($payzenResponse->get('currency'));
                    $amount = round(
                        $currency->convertAmountToFloat($payzenResponse->get('amount')),
                        $currency->getDecimals()
                    );

                    $amountDetail = $amount . ' ' . $currency->getAlpha3();

                    if ($payzenResponse->get('effective_currency') &&
                         ($payzenResponse->get('currency') !== $payzenResponse->get('effective_currency'))) {
                        $effectiveCurrency = PayzenApi::findCurrencyByNumCode($payzenResponse->get('effective_currency'));

                        $effectiveAmount = round(
                            $effectiveCurrency->convertAmountToFloat($payzenResponse->get('effective_amount')),
                            $effectiveCurrency->getDecimals()
                        );

                        $amountDetail = $effectiveAmount . ' ' . $effectiveCurrency->getAlpha3() . ' (' . $amountDetail . ')';
                    }

                    $additionalInfo = [
                        'Transaction Type' => 'CREDIT',
                        'Amount' => $amountDetail,
                        'Transaction ID' => $transactionId,
                        'Transaction Status' => $payzenResponse->get('trans_status'),
                        'Payment Mean' => $payzenResponse->get('card_brand'),
                        'Card Number' => $payzenResponse->get('card_number'),
                        'Expiration Date' => $expiry,
                        '3-DS Certificate' => ''
                    ];

                    $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND;

                    $this->paymentHelper->addTransaction(
                        $order->getPayment(),
                        $transactionType,
                        $transactionId,
                        $additionalInfo
                    );
                } else {
                    // update transaction info
                    $this->paymentHelper->updatePaymentInfo($order, $payzenResponse);
                }

                $order->save();

                return $controller->renderResponse($payzenResponse->getOutputForPlatform('payment_ok_already_done'));
            } elseif ($order->isCanceled() && ! $payzenResponse->isAcceptedPayment()) {
                $this->dataHelper->log("Order #{$order->getId()} cancelation is confirmed.");
                return $controller->renderResponse($payzenResponse->getOutputForPlatform('payment_ko_already_done'));
            } else {
                // error case, the client returns with an error code but the payment already has been accepted
                $this->dataHelper->log(
                    "Order #{$order->getId()} has been validated but we receive a payment error code !",
                    \Psr\Log\LogLevel::ERROR
                );
                return $controller->renderResponse($payzenResponse->getOutputForPlatform('payment_ko_on_order_ok'));
            }
        }
    }
}
