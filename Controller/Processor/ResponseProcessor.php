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

    public function execute(\Lyranetwork\Payzen\Api\ResponseActionInterface $controller)
    {
        $request = $controller->getRequest()->getParams();

        // loading order
        $orderId = key_exists('vads_order_id', $request) ? $request['vads_order_id'] : 0;
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($orderId);

        // get store id from order
        $storeId = $order->getStore()->getId();

        // load API response
        $payzenResponse = $this->payzenResponseFactory->create(
            [
                'params' => $request,
                'ctx_mode' => $this->dataHelper->getCommonConfigData('ctx_mode', $storeId),
                'key_test' => $this->dataHelper->getCommonConfigData('key_test', $storeId),
                'key_prod' => $this->dataHelper->getCommonConfigData('key_prod', $storeId)
            ]
        );

        if (! $payzenResponse->isAuthentified()) {
            // authentification failed
            $this->dataHelper->log(
                "{$this->dataHelper->getIpAddress()} tries to access payzen/payment/response page without valid signature with parameters: " . json_encode($request),
                \Psr\Log\LogLevel::ERROR
            );

            return $controller->redirectError($order);
        }

        $this->dataHelper->log("Request authenticated for order #{$order->getId()}.");

        if (! $orderId) {
            $this->dataHelper->log(
                "Order ID not returned. Payment result : " . $payzenResponse->getLogMessage(),
                \Psr\Log\LogLevel::ERROR
            );
            return $controller->redirectError($order);
        }

        if ($order->getStatus() == 'pending_payment') {
            // order waiting for payment
            $this->dataHelper->log("Order #{$order->getId()} is waiting payment.");
            $this->dataHelper->log("Payment result for order #{$order->getId()} : " . $payzenResponse->getLogMessage());

            if ($payzenResponse->isAcceptedPayment()) {
                $this->dataHelper->log("Payment for order #{$order->getId()} has been confirmed by client return !" .
                     " This means the notification URL did not work.", \Psr\Log\LogLevel::WARNING);

                // save order and optionally create invoice
                $this->paymentHelper->registerOrder($order, $payzenResponse);

                // display success page
                return $controller->redirectResponse(
                    $order,
                    true /* is success ? */,
                    true /* notification url warn in TEST mode */
                );
            } else {
                $this->dataHelper->log("Payment for order #{$order->getId()} has failed.");

                // cancel order
                $this->paymentHelper->cancelOrder($order, $payzenResponse);

                // redirect to cart page
                return $controller->redirectResponse($order, false /* is success ? */);
            }
        } else {
            // payment already processed
            $this->dataHelper->log("Order #{$order->getId()} has already been processed.");

            $acceptedStatus = $this->dataHelper->getCommonConfigData('registered_order_status', $storeId);
            $successStatuses = [
            $acceptedStatus,
            'complete' /* case of virtual orders */,
            'payment_review' /* case of pending payments like Oney */,
            'fraud' /* fraud status is taken as successful because it's just a suspicion */,
            'payzen_to_validate' /* payment will be done after manual validation */
            ];

            if ($payzenResponse->isAcceptedPayment() && in_array($order->getStatus(), $successStatuses)) {
                $this->dataHelper->log("Order #{$order->getId()} is confirmed.");
                return $controller->redirectResponse($order, true /* is success ? */);
            } elseif ($order->isCanceled() && ! $payzenResponse->isAcceptedPayment()) {
                $this->dataHelper->log("Order #{$order->getId()} cancelation is confirmed.");
                return $controller->redirectResponse($order, false /* is success ? */);
            } else {
                // error case, the client returns with an error code but the payment has already been accepted
                $this->dataHelper->log(
                    "Order #{$order->getId()} has been validated but we receive a payment error code !",
                    \Psr\Log\LogLevel::ERROR
                );
                return $controller->redirectError($order);
            }
        }
    }
}
