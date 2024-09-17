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

use Lyranetwork\Payzen\Model\ResponseException;
use Magento\Framework\DataObject;

class Response extends \Lyranetwork\Payzen\Controller\Payment\Response
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Lyranetwork\Payzen\Model\Api\Form\ResponseFactory
     */
    protected $payzenResponseFactory;

    /**
     * @var \Lyranetwork\Payzen\Helper\Rest
     */
    protected $restHelper;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Checkout\Model\Type\Onepage
     */
    protected $onepage;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor $responseProcessor
     * @param \Lyranetwork\Payzen\Controller\Result\RedirectFactory $payzenRedirectFactory
     * @param \Lyranetwork\Payzen\Helper\Rest $restHelper
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Magento\Checkout\Model\Type\Onepage $onepage
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor $responseProcessor,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Lyranetwork\Payzen\Helper\Rest $restHelper,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Checkout\Model\Type\Onepage $onepage
    ) {
        $this->restHelper = $restHelper;
        $this->quoteManagement = $quoteManagement;
        $this->onepage = $onepage;
        $this->orderFactory = $responseProcessor->getOrderFactory();
        $this->payzenResponseFactory = $responseProcessor->getPayzenResponseFactory();

        parent::__construct($context, $quoteRepository, $responseProcessor, $resultPageFactory);
    }

    public function execute()
    {
        // Clear quote data.
        $this->dataHelper->getCheckout()->unsLastQuoteId()
            ->unsLastSuccessQuoteId()
            ->clearHelperData();

        return parent::execute();
    }

    protected function prepareResponse($params)
    {
        // Check the validity of the request.
        if (! $this->restHelper->checkResponseFormat($params)) {
            throw new ResponseException('Invalid response received. Content: ' . json_encode($params));
        }

        $answer = json_decode($params['kr-answer'], true);
        if (! is_array($answer)) {
            throw new ResponseException('Invalid response received. Content: ' . json_encode($params));
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

        if ($response->getExtInfo('from_account')) {
            $storeId = $response->getExtInfo('store_id');

            // Check the authenticity of the request.
            $this->checkResponseHash($params, $storeId);

            return [
                'response' => $response,
                'from_account' => true
            ];
        }

        $orderId = (int) $response->get('order_id');
        if (! $orderId) {
            $this->dataHelper->log("Received empty Order ID.", \Psr\Log\LogLevel::ERROR);
            throw new ResponseException('Order ID is empty.');
        }

        $order = $this->orderFactory->create();
        $order->loadByIncrementId($orderId);

        if (! $order->getId()) {
            $this->dataHelper->log("Order not found with ID #{$orderId}.", \Psr\Log\LogLevel::ERROR);
            throw new ResponseException("Order not found with ID #{$orderId}.");
        }

        // Disable quote.
        $quote = $this->quoteRepository->get($order->getQuoteId());
        if ($quote->getIsActive()) {
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
            $this->dataHelper->log("Cleared quote, order ID: #{$orderId}.");
        }

        $storeId = $order->getStore()->getId();

        // Check the authenticity of the request.
        $this->checkResponseHash($params, $storeId);

        return [
            'response' => $response,
            'order' => $order
        ];
    }

    private function checkResponseHash($params, $storeId)
    {
        if (! $this->restHelper->checkResponseHash($params, $this->restHelper->getReturnKey($storeId))) {
            // Authentication failed.
            throw new ResponseException(
                "{$this->dataHelper->getIpAddress()} tries to access payzen/payment_rest/response page without valid signature with parameters: " . json_encode($params)
            );
        }
    }
}
