<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Payment;

use Lyranetwork\Payzen\Helper\Payment;

class Response extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor
     */
    protected $responseProcessor;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Lyranetwork\Payzen\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor $responseProcessor
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor $responseProcessor,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Lyranetwork\Payzen\Helper\Payment $paymentHelper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->responseProcessor = $responseProcessor;
        $this->resultPageFactory = $resultPageFactory;
        $this->dataHelper = $responseProcessor->getDataHelper();
        $this->paymentHelper = $paymentHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        // Empty order model.
        $order = null;

        try {
            $params = $this->getRequest()->getParams();
            $data = $this->prepareResponse($params);

            if (isset($data['from_account'])) {
                return $this->redirectToAccount($data['response']);
            }

            $order = $data['order'];
            $response = $data['response'];
            $result = $this->responseProcessor->execute($order, $response);

            $updateOrder = $this->getRequest()->getParam('payzen_update_order', false);
            if ($updateOrder) {
                return null;
            }

            return $this->redirectResponse($order, $result['case'], $result['warn']);
        } catch (\Lyranetwork\Payzen\Model\ResponseException $e) {
            $this->dataHelper->log($e->getMessage(), \Psr\Log\LogLevel::ERROR);
            return $this->redirectError($order);
        }
    }

    protected function prepareResponse($params)
    {
        return $this->responseProcessor->prepareResponse($params);
    }

    /**
     * Redirect to error page (when technical error occurred).
     *
     * @param \Magento\Sales\Model\Order $order
     */
    protected function redirectError($order = null)
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        if ($order) {
            // For Magento versions < 2.4.0.
            $this->dataHelper->getCheckout()
                ->setLastQuoteId($order->getQuoteId())
                ->setLastOrderId($order->getId());
        }

        $storeId = ($order && $order->getStore()->getId()) ? $order->getStore()->getId() : $this->dataHelper->getCheckoutStoreId();

        $this->dataHelper->log('Redirecting to one page checkout failure page.' . ($order ? " Order #{$order->getIncrementId()}." : ''));
        return $this->createResult('checkout/onepage/failure', ['_scope' => $storeId]);
    }

    /**
     * Redirect to result page (according to payment status).
     *
     * @param \Magento\Sales\Model\Order $order
     * @param int $case
     * @param bool $checkUrlWarn
     */
    protected function redirectResponse($order, $case, $checkUrlWarn = false)
    {
        /**
         * @var Magento\Checkout\Model\Session $checkout
         */
        $checkout = $this->dataHelper->getCheckout();

        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $storeId = $order->getStore()->getId();
        if ($this->dataHelper->getCommonConfigData('ctx_mode', $storeId) === 'TEST') {
            $features = \Lyranetwork\Payzen\Helper\Data::$pluginFeatures;
            if ($features['prodfaq']) {
                // Display going to production message.
                $message = __('GOING INTO PRODUCTION: You want to know how to put your shop into production mode, please read chapters &laquo; Proceeding to test phase &raquo; and &laquo; Shifting the shop to production mode &raquo; in the documentation of the module.');
                $this->messageManager->addNoticeMessage($message);
            }

            if ($checkUrlWarn) {
                // Order not validated by notification URL. In TEST mode, user is webmaster.
                // So display a warning about notification URL not working.
                if ($this->dataHelper->isMaintenanceMode()) {
                    $message = __('The shop is in maintenance mode.The automatic notification cannot work.');
                } else {
                    $message = __('The automatic validation has not worked. Have you correctly set up the notification URL in your PayZen Back Office?');
                    $message .= '&nbsp;';
                    $message .= __('For understanding the problem, please read the documentation of the module:&nbsp;&nbsp;&nbsp;- Chapter &laquo; To read carefully before going further &raquo;&nbsp;&nbsp;&nbsp;- Chapter &laquo; Notification URL settings &raquo;');
                }

                $this->messageManager->addErrorMessage($message);
            }
        }

        if ($case === Payment::SUCCESS) {
            $checkout->setLastQuoteId($order->getQuoteId())
                ->setLastSuccessQuoteId($order->getQuoteId())
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            $this->dataHelper->log("Redirecting to one page checkout success page for order #{$order->getIncrementId()}.");
            $resultRedirect = $this->createResult('checkout/onepage/success', ['_scope' => $storeId]);
        } else {
            if ($case === Payment::FAILURE) {
                $this->messageManager->addWarningMessage(__('Your payment was not accepted. Please, try to re-order.'));
            }

            $this->paymentHelper->restoreQuoteForOrder($order);

            $this->dataHelper->log("Redirecting to cart page for order #{$order->getIncrementId()}.");

            return $this->createResult('checkout/cart', ['_scope' => $storeId]);
        }

        return $resultRedirect;
    }

    protected function redirectToAccount($response)
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        if ($response->get('identifier') || ($response->get('identifier_status') == 'CREATED')) {
            $this->messageManager->addSuccessMessage(__('Payment means successfully added.'));
        } else {
            $this->messageManager->addErrorMessage(__('Unable to add payment means to your account.'));
        }

        return $this->createResult('vault/cards/listaction', ['_scope' => '1']);
    }

    private function createResult($path, $params)
    {
        /**
         * @var \Magento\Framework\Controller\Result\Redirect $result
         */
        $result = $this->resultRedirectFactory->create();
        $result->setPath($path, $params);
        return $result;
    }
}
