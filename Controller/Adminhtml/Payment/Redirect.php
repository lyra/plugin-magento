<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Adminhtml\Payment;

use Lyranetwork\Payzen\Model\OrderException;

class Redirect extends \Magento\Backend\App\Action
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Lyranetwork\Payzen\Controller\Processor\RedirectProcessor
     */
    protected $redirectProcessor;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Lyranetwork\Payzen\Controller\Processor\RedirectProcessor $redirectProcessor
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Lyranetwork\Payzen\Controller\Processor\RedirectProcessor $redirectProcessor,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->redirectProcessor = $redirectProcessor;
        $this->resultPageFactory = $resultPageFactory;
        $this->orderRepository = $orderRepository;
        $this->dataHelper = $redirectProcessor->getDataHelper();

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $order = $this->getAndCheckOrder();

            $this->redirectProcessor->execute($order);

            return $this->forward();
        } catch (\Lyranetwork\Payzen\Model\OrderException $e) {
            return $this->back($e->getMessage());
        }
    }

    /**
     * Get order to pay from session and check it (amount, already processed, ...).
     */
    private function getAndCheckOrder()
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $id = $this->getRequest()->getParam('order_id');

        // Check that there is an order to pay.
        try {
            $order = $this->orderRepository->get($id);
        } catch (\Exception $e) {
            $this->dataHelper->log("No order to pay. It may be a direct access to redirection page."
                . " [Order = {$id}] [IP = {$this->dataHelper->getIpAddress()}].");
            throw new OrderException('Order not found in session.');
        }

        // Check that there is products in cart.
        if (! $order->getTotalDue()) {
            $this->dataHelper->log("Payment attempt with no amount. [Order = {$order->getId()}]"
                . " [IP = {$this->dataHelper->getIpAddress()}].");
            throw new OrderException('Order total is empty.');
        }

        // Check that order is not processed yet.
        if (! $this->dataHelper->getCheckout()->getLastSuccessQuoteId()) {
            $this->dataHelper->log("Payment attempt with a quote already processed. [Order = {$order->getId()}]"
                . " [IP = {$this->dataHelper->getIpAddress()}].");
            throw new OrderException('Order payment already processed.');
        }

        $this->dataHelper->getCheckout()->unsLastSuccessQuoteId();

        return $order;
    }

    /**
     * Redirect to checkout initial page (when payment cannot be done).
     */
    private function back($msg)
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);
        $this->messageManager->addError($msg);

        $this->dataHelper->log($msg . ' Redirecting to backend create order page.');

        /**
         * @var \Magento\Framework\Controller\Result\Redirect $resultRedirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order_create/');

        return $resultRedirect;
    }

    /**
     * Display redirection page.
     */
    private function forward()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Payment gateway redirection'));
        return $resultPage;
    }
}
