<?php
/**
 * PayZen V2-Payment Module version 2.1.2 for Magento 2.x. Support contact : support@payzen.eu.
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
namespace Lyranetwork\Payzen\Controller\Payment;

use Lyranetwork\Payzen\Model\OrderException;

class Redirect extends \Magento\Framework\App\Action\Action implements \Lyranetwork\Payzen\Api\RedirectActionInterface
{

    /**
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     *
     * @var \Lyranetwork\Payzen\Controller\Processor\RedirectProcessor
     */
    protected $redirectProcessor;

    /**
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     *
     * @var \Lyranetwork\Payzen\Controller\Result\RedirectFactory
     */
    protected $payzenRedirectFactory;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Controller\Processor\RedirectProcessor $redirectProcessor
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Lyranetwork\Payzen\Controller\Result\RedirectFactory $payzenRedirectFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Controller\Processor\RedirectProcessor $redirectProcessor,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Lyranetwork\Payzen\Controller\Result\RedirectFactory $payzenRedirectFactory
    ) {
        $this->orderFactory = $orderFactory;
        $this->dataHelper = $dataHelper;
        $this->redirectProcessor = $redirectProcessor;
        $this->resultPageFactory = $resultPageFactory;
        $this->payzenRedirectFactory = $payzenRedirectFactory;

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
        /**
         *
         * @var Magento\Checkout\Model\Session $checkout
         */
        $checkout = $this->dataHelper->getCheckout();

        // load order
        $lastIncrementId = $checkout->getLastRealOrderId();

        // check that there is an order to pay
        if (empty($lastIncrementId)) {
            $this->dataHelper->log("No order to pay. It may be a direct access to redirection page." .
                     " [IP = {$this->dataHelper->getIpAddress()}].");
            throw new OrderException('Order not found in session.');
        }

        $order = $this->orderFactory->create();
        $order->loadByIncrementId($lastIncrementId);

        // check that there is products in cart
        if ($order->getTotalDue() == 0) {
            $this->dataHelper->log("Payment attempt with no amount. [Order = {$order->getId()}]" .
                     " [IP = {$this->dataHelper->getIpAddress()}].");
            throw new OrderException('Order total is empty.');
        }

        // check that order is not processed yet
        if (! $checkout->getLastSuccessQuoteId()) {
            $this->dataHelper->log("Payment attempt with a quote already processed. [Order = {$order->getId()}]" .
                     " [IP = {$this->dataHelper->getIpAddress()}].");
            throw new OrderException('Order payment already processed.');
        }

        // clear quote data
        $checkout->unsLastQuoteId()
            ->unsLastSuccessQuoteId()
            ->clearHelperData();

        return $order;
    }

    /**
     * Redirect to checkout initial page (when payment cannot be done).
     */
    public function back($msg)
    {
        // clear all messages in session
        $this->messageManager->getMessages(true);
        $this->dataHelper->log('Redirecting to cart page.');

        $resultRedirect = $this->payzenRedirectFactory->create();
        $resultRedirect->setIframe($this->getRequest()
            ->getParam('iframe', false));
        $resultRedirect->setPath('checkout/cart');

        return $resultRedirect;
    }

    /**
     * Display redirection page.
     */
    public function forward()
    {
        $resultPage = $this->resultPageFactory->create();

        if ($this->getRequest()->getParam('iframe', false)) {
            $resultPage->addHandle('payzen_payment_iframe_redirect');
        } else {
            $resultPage->addHandle('payzen_payment_form_redirect');
            $resultPage->getConfig()
                ->getTitle()
                ->set(__('Payment platform redirection'));
        }

        return $resultPage;
    }
}
