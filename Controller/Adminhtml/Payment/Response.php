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

use Lyranetwork\Payzen\Helper\Payment;
use Lyranetwork\Payzen\Model\ResponseException;

class Response extends \Magento\Backend\App\Action
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor
     */
    protected $responseProcessor;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor $responseProcessor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor $responseProcessor
    ) {
        $this->responseProcessor = $responseProcessor;
        $this->dataHelper = $responseProcessor->getDataHelper();

        parent::__construct($context);
    }

    public function execute()
    {
        // Empty order model.
        $order = null;

        try {
            $params = $this->getRequest()->getParams();
            $data = $this->responseProcessor->prepareResponse($params);

            $order = $data['order'];
            $response = $data['response'];

            $result = $this->responseProcessor->execute($order, $response);

            return $this->redirectResponse($order, $result['case'], $result['warn']);
        } catch (ResponseException $e) {
            $this->dataHelper->log($e->getMessage(), \Psr\Log\LogLevel::ERROR);
            return $this->redirectError($order);
        }
    }

    /**
     * Redirect to error page (when technical error occurred).
     *
     * @param \Magento\Sales\Model\Order $order
     */
    private function redirectError($order = null)
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);
        $this->messageManager->addError(__('An error has occurred during the payment process.'));

        $this->dataHelper->log('Redirecting to order creation page.' . ($order ? " Order #{$order->getIncrementId()}." : ''));

        /**
         * @var \Magento\Framework\Controller\Result\Redirect $resultRedirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order_create/');

        return $resultRedirect;
    }

    /**
     * Redirect to result page (according to payment status).
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $case
     * @param bool $checkUrlWarn
     */
    private function redirectResponse($order, $case, $checkUrlWarn = false)
    {
        /**
         * @var Magento\Backend\Model\Session\Quote $checkout
         */
        $checkout = $this->dataHelper->getCheckout();

        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $storeId = $order->getStore()->getId();
        if ($this->dataHelper->getCommonConfigData('ctx_mode', $storeId) === 'TEST') {
            $features = \Lyranetwork\Payzen\Helper\Data::$pluginFeatures;
            if ($features['prodfaq']) {
                // Display going to production message.
                $message = __('<u><p>GOING INTO PRODUCTION:</u></p> You want to know how to put your shop into production mode, please read chapters &laquo; Proceeding to test phase &raquo; and &laquo; Shifting the shop to production mode &raquo; in the documentation of the module.');
                $this->messageManager->addNotice($message);
            }

            if ($checkUrlWarn) {
                // Order not validated by notification URL. In TEST mode, user is webmaster.
                // So display a warning about notification URL not working.

                if ($this->dataHelper->isMaintenanceMode()) {
                    $message = __('The shop is in maintenance mode.The automatic notification cannot work.');
                } else {
                    $message = __('The automatic validation has not worked. Have you correctly set up the notification URL in your PayZen Back Office?');
                    $message .= '<br /><br />';
                    $message .= __('For understanding the problem, please read the documentation of the module:<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo; To read carefully before going further &raquo;<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo; Notification URL settings &raquo;');
                }

                $this->messageManager->addError($message);
            }
        }

        if ($case === Payment::SUCCESS) {
            $this->messageManager->addSuccess(
                __('The payment was successful. Your order was registered successfully.')
            );
        } elseif ($case === Payment::FAILURE) {
            $this->messageManager->addWarning(__('Your payment was not accepted. Please, try to re-order.'));
        }

        $this->dataHelper->log("Redirecting to order view or order index page for order #{$order->getIncrementId()}.");

        /**
         * @var \Magento\Framework\Controller\Result\Redirect $resultRedirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($this->_authorization->isAllowed('Magento_Sales::actions_view')) {
            $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
        } else {
            $resultRedirect->setPath('sales/order/index');
        }

        return $resultRedirect;
    }
}
