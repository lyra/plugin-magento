<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Payment\Iframe;

class Loader extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var\Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->dataHelper = $dataHelper;
        $this->orderFactory = $orderFactory;
        $this->quoteRepository = $quoteRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        // Check if it is a cancelled order.
        if ($this->getRequest()->getParam('mode', false) === 'cancel') {
            // Load order.
            $checkout = $this->dataHelper->getCheckout();
            $lastIncrementId = $checkout->getData('payzen_last_real_id');

            $this->dataHelper->log("Payment within iframe is cancelled for order #{$lastIncrementId}.");

            $order = $this->orderFactory->create();
            $order->loadByIncrementId($lastIncrementId);

            if ($order->getId()) {
                $order->registerCancellation(__('Payment cancelled.'))->save();
                $checkout->setData('payzen_last_real_id', null);

                $this->dataHelper->log("Restore cart for order #{$order->getIncrementId()} to allow re-order quicker.");

                $quote = $this->quoteRepository->get($order->getQuoteId());
                if ($quote->getId()) {
                    $quote->setIsActive(true)->setReservedOrderId(null);
                    $this->quoteRepository->save($quote);

                    // To comply with Magento\Checkout\Model\Session::restoreQuote() method.
                    $checkout->replaceQuote($quote)->unsLastRealOrderId();
                    $this->_eventManager->dispatch('restore_quote', ['order' => $order, 'quote' => $quote]);
                }
            }
        }

        $resultPage = $this->resultPageFactory->create();

        // Remove all assets to let iframe empty.
        $assets = $resultPage->getConfig()
            ->getAssetCollection()
            ->getAll();
        foreach (array_keys($assets) as $identifier) {
            $resultPage->getConfig()
                ->getAssetCollection()
                ->remove($identifier);
        }

        return $resultPage;
    }
}
