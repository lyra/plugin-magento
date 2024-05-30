<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Payment\Rest;

use \Lyranetwork\Payzen\Helper\Payment;
use Lyranetwork\Payzen\Model\ResponseException;
use Magento\Framework\DataObject;

class Check extends \Lyranetwork\Payzen\Controller\Payment\Check
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Lyranetwork\Payzen\Model\Api\Form\ResponseFactory
     */
    protected $payzenResponseFactory;

    /**
     * @var \Lyranetwork\Payzen\Helper\Rest
     */
    protected $restHelper;

    /**
     * @var \Magento\Checkout\Model\Type\Onepage
     */
    protected $onepage;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Lyranetwork\Payzen\Controller\Processor\CheckProcessor $checkProcessor
     * @param \Magento\Framework\Controller\Result\RawFactory $rawResultFactory
     * @param \Lyranetwork\Payzen\Helper\Rest $restHelper
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Checkout\Model\Type\Onepage $onepage
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Lyranetwork\Payzen\Controller\Processor\CheckProcessor $checkProcessor,
        \Magento\Framework\Controller\Result\RawFactory $rawResultFactory,
        \Lyranetwork\Payzen\Helper\Rest $restHelper,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Type\Onepage $onepage
    ) {
        $this->restHelper = $restHelper;
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->onepage = $onepage;
        $this->dataHelper = $checkProcessor->getDataHelper();
        $this->storeManager = $checkProcessor->getStoreManager();
        $this->orderFactory = $checkProcessor->getOrderFactory();
        $this->payzenResponseFactory = $checkProcessor->getPayzenResponseFactory();

        parent::__construct($context, $checkProcessor, $rawResultFactory);
    }

    protected function prepareResponse($params)
    {
        // Check the validity of the request.
        if (! $this->restHelper->checkResponseFormat($params)) {
            $this->dataHelper->log('Invalid response received. Content: ' . json_encode($params), \Psr\Log\LogLevel::ERROR);
            throw new ResponseException('<span style="display:none">KO-Invalid IPN request received.' . "\n" . '</span>');
        }

        $answer = json_decode($params['kr-answer'], true);
        if (! is_array($answer)) {
            $this->dataHelper->log('Invalid response received. Content: ' . json_encode($params), \Psr\Log\LogLevel::ERROR);
            throw new ResponseException('<span style="display:none">KO-Invalid IPN request received.' . "\n" . '</span>');
        }

        // Wrap payment result to use traditional order creation tunnel.
        $data = $this->restHelper->convertRestResult($answer);

        // Convert REST result to standard form response.
        $response = $this->payzenResponseFactory->create(
            [
                'params' => $data,
                'ctx_mode' => null,
                'key_test' => '',
                'key_prod' => '',
                'algo' => null
            ]
        );

        $orderId = (int) $response->get('order_id');
        if (! $orderId) {
            $this->dataHelper->log("Received empty Order ID.", \Psr\Log\LogLevel::ERROR);

            $abandonedPayment = isset($answer['orderCycle']) && ($answer['orderCycle'] === 'CLOSED') && isset($answer['orderStatus']) && ($answer['orderStatus'] === 'ABANDONED') && empty($response->getTransStatus());
            if ($abandonedPayment) {
                $this->dataHelper->log('Abandoned payment IPN ignored.');
                $this->dataHelper->log('IPN URL PROCESS END.');
                die('Abandoned payment IPN ignored.');
            } else {
                throw new ResponseException('Order ID is empty.');
            }
        }

        $order = $this->orderFactory->create();
        $order->loadByIncrementId($orderId);

        if (! $order->getId()) {
            // Backward compatibility with older versions.
            if ($quoteId = (int) $response->getExtInfo('quote_id')) {
                if ($this->quoteRepository->get($quoteId)->getId()) {
                    $this->dataHelper->log("Quote not found with ID #{$quoteId}.", \Psr\Log\LogLevel::ERROR);
                    throw new ResponseException($response->getOutputForGateway('order_not_found'));
                }

                $quote = $this->quoteRepository->get($quoteId);
                $this->saveOrderForQuote($quote);

                // Dispatch save order event.
                $result = new DataObject();
                $result->setData('success', true);
                $result->setData('error', false);

                $this->_eventManager->dispatch(
                    'checkout_controller_onepage_saveOrder',
                    [
                        'result' => $result,
                        'action' => $this
                    ]
                );

                // Load newly created order.
                $order->loadByIncrementId($quote->getReservedOrderId());
                if (! $order->getId()) {
                    $this->dataHelper->log("Order cannot be created. Quote ID: #{$quoteId}, reserved order ID: #{$quote->getReservedOrderId()}.", \Psr\Log\LogLevel::ERROR);
                    throw new ResponseException($response->getOutputForGateway('ko', 'Error when trying to create order.'));
                }
            } else {
                $this->dataHelper->log("Order not found with ID #{$orderId}.", \Psr\Log\LogLevel::ERROR);
                throw new ResponseException("Order not found with ID #{$orderId}.");
            }
        }

        // Disable quote.
        $quote = $this->quoteRepository->get($order->getQuoteId());
        if ($quote->getIsActive()) {
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
            $this->dataHelper->log("Cleared quote, order ID: #{$orderId}.");
        }

        // Case of failure or expiration when retries are enabled and Update Order option not is not enabled, do nothing before last attempt.
        if (! $response->isAcceptedPayment() && ($answer['orderCycle'] !== 'CLOSED') && ! $response->getExtInfo('update_order')) {
            $this->dataHelper->log("Payment is not accepted but buyer can try to re-order. Do not process order at this time.
                Order ID: #{$orderId}.");
            throw new ResponseException($response->getOutputForGateway('payment_ko_bis'));
        }

        // Get store id from order.
        $storeId = $order->getStore()->getId();

        // Init app with correct store ID.
        $this->storeManager->setCurrentStore($storeId);

        // Check the authenticity of the request.
        if (! $this->restHelper->checkResponseHash($params, $this->restHelper->getPrivateKey($storeId))) {
            // Authentication failed.
            $this->dataHelper->log(
                "{$this->dataHelper->getIpAddress()} tries to access payzen/payment_rest/response page without valid signature with parameters: " . json_encode($params),
                \Psr\Log\LogLevel::ERROR
            );

            throw new ResponseException($response->getOutputForGateway('auth_fail'));
        }

        return [
            'response' => $response,
            'order' => $order
        ];
    }

    protected function saveOrderForQuote($quote)
    {
        try {
            $this->onepage->setQuote($quote);
            if ($quote->getCustomerId()) {
                $this->onepage->getCustomerSession()->loginById($quote->getCustomerId());
            }

            $this->onepage->saveOrder();
        } catch (Exception $e) {
            $this->dataHelper->log("Order cannot be created. Quote ID: #{$quote->getId()}, reserved order ID: #{$quote->getReservedOrderId()}.", \Psr\Log\LogLevel::ERROR);
            throw new ResponseException('Error when trying to create order.');
        }
    }
}
