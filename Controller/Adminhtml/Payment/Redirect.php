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
namespace Lyranetwork\Payzen\Controller\Adminhtml\Payment;

use Lyranetwork\Payzen\Model\OrderException;

class Redirect extends \Magento\Backend\App\Action implements \Lyranetwork\Payzen\Api\RedirectActionInterface
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
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Controller\Processor\RedirectProcessor $redirectProcessor
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Controller\Processor\RedirectProcessor $redirectProcessor,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->dataHelper = $dataHelper;
        $this->redirectProcessor = $redirectProcessor;
        $this->resultPageFactory = $resultPageFactory;
        $this->orderRepository = $orderRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        return $this->redirectProcessor->execute($this);
    }

    /**
     * Get order to pay from session and check it (amount, already processed, ...).
     */
    public function getAndCheckOrder()
    {
        // clear all messages in session
        $this->messageManager->getMessages(true);

        $id = $this->getRequest()->getParam('order_id');

        // check that there is an order to pay
        try {
            $order = $this->orderRepository->get($id);
        } catch (\Exception $e) {
            $this->dataHelper->log("No order to pay. It may be a direct access to redirection page."
                . " [Order = {$id}] [IP = {$this->dataHelper->getIpAddress()}].");
            throw new OrderException('Order not found in session.');
        }

        // check that there is products in cart
        if ($order->getTotalDue() == 0) {
            $this->dataHelper->log("Payment attempt with no amount. [Order = {$order->getId()}]"
                . " [IP = {$this->dataHelper->getIpAddress()}].");
            throw new OrderException('Order total is empty.');
        }

        // check that order is not processed yet
        if (!$this->dataHelper->getCheckout()->getLastSuccessQuoteId()) {
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
    public function back($msg)
    {
        // clear all messages in session
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
    public function forward()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Payment platform redirection'));
        return $resultPage;
    }
}
