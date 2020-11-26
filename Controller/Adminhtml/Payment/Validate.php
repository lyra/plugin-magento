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

class Validate extends \Magento\Backend\App\Action implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

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
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository

    ) {
        $this->dataHelper = $dataHelper;
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->orderRepository = $orderRepository;

        parent::__construct($context);
    }

    /**
     * Action called when Validate payment button is clicked in backend order view.
     */
    public function execute()
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            // Retrieve order to validate.
            $id = $this->getRequest()->getParam('order_id');
            $order = $this->orderRepository->get($id);
            if (! $order->getId()) {
                $this->messageManager->addError(__('This order no longer exists.'));
                $resultRedirect->setPath('sales/*/');

                return $resultRedirect;
            }

            $this->coreRegistry->register(
                \Lyranetwork\Payzen\Block\Constants::SALES_ORDER,
                $order
            );
            $this->coreRegistry->register(
                \Lyranetwork\Payzen\Block\Constants::CURRENT_ORDER,
                $order
            );

            $payment = $order->getPayment();
            $payment->getMethodInstance()->validatePayment($payment);

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error has occurred during the validation process.'));
        }

        $resultRedirect->setPath('sales/order/view',['order_id' => $order->getId()]);

        return $resultRedirect;
    }
}
