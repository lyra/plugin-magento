<?php
/**
 * PayZen V2-Payment Module version 2.3.2 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Adminhtml\Payment;

use Lyranetwork\Payzen\Helper\Payment;

class Response extends \Magento\Backend\App\Action implements \Lyranetwork\Payzen\Api\ResponseActionInterface
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
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor $responseProcessor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor $responseProcessor
    ) {
        $this->dataHelper = $dataHelper;
        $this->quoteRepository = $quoteRepository;
        $this->responseProcessor = $responseProcessor;

        parent::__construct($context);
    }

    public function execute()
    {
        return $this->responseProcessor->execute($this);
    }

    /**
     * Redirect to error page (when technical error occurred).
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function redirectError($order)
    {
        // clear all messages in session
        $this->messageManager->getMessages(true);
        $this->messageManager->addError(__('An error has occurred during the payment process.'));

        $this->dataHelper->log("Redirecting to order creation page for order #{$order->getId()}.");

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
    public function redirectResponse($order, $case, $checkUrlWarn = false)
    {
        /**
         * @var Magento\Backend\Model\Session\Quote $checkout
         */
        $checkout = $this->dataHelper->getCheckout();

        // clear all messages in session
        $this->messageManager->getMessages(true);

        $storeId = $order->getStore()->getId();
        if ($this->dataHelper->getCommonConfigData('ctx_mode', $storeId) == 'TEST') {
            $features = \Lyranetwork\Payzen\Helper\Data::$pluginFeatures;
            if ($features['prodfaq']) {
                // display going to production message
                $message = __('<u><p>GOING INTO PRODUCTION:</u></p> You want to know how to put your shop into production mode, please read chapters &laquo; Proceeding to test phase &raquo; and &laquo; Shifting the shop to production mode &raquo; in the documentation of the module.');
                $this->messageManager->addNotice($message);
            }

            if ($checkUrlWarn) {
                // order not validated by notification URL, in TEST mode, user is webmaster
                // so display a warning about notification URL not working

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

        $this->dataHelper->log("Redirecting to order view or order index page for order #{$order->getId()}.");

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
