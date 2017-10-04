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

class Response extends \Magento\Framework\App\Action\Action implements \Lyranetwork\Payzen\Api\ResponseActionInterface
{

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     *
     * @var \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor
     */
    protected $responseProcessor;

    /**
     *
     * @var \Lyranetwork\Payzen\Controller\Result\RedirectFactory
     */
    protected $payzenRedirectFactory;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor $responseProcessor
     * @param \Lyranetwork\Payzen\Controller\Result\RedirectFactory $payzenRedirectFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Lyranetwork\Payzen\Controller\Processor\ResponseProcessor $responseProcessor,
        \Lyranetwork\Payzen\Controller\Result\RedirectFactory $payzenRedirectFactory
    ) {
        $this->dataHelper = $dataHelper;
        $this->quoteRepository = $quoteRepository;
        $this->responseProcessor = $responseProcessor;
        $this->payzenRedirectFactory = $payzenRedirectFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        return $this->responseProcessor->execute($this);
    }

    /**
     * Redirect to error page (when technical error occured).
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function redirectError($order)
    {
        // clear all messages in session
        $this->messageManager->getMessages(true);

        $this->dataHelper->getCheckout()
            ->setLastQuoteId($order->getQuoteId())
            ->setLastOrderId($order->getId());

        $this->dataHelper->log('Redirecting to one page checkout failure page.');

        /**
         *
         * @var \Magento\Framework\Controller\Result\Redirect $resultRedirect
         */
        $resultRedirect = $this->payzenRedirectFactory->create();
        $resultRedirect->setIframe($this->getRequest()->getParam('iframe', false));
        $resultRedirect->setPath(
            'checkout/onepage/failure',
            [
                '_scope' => $order->getStore()->getId()
            ]
        );

        return $resultRedirect;
    }

    /**
     * Redirect to result page (according to payment status).
     *
     * @param \Magento\Sales\Model\Order $order
     * @param bool $success
     * @param bool $checkUrlWarn
     */
    public function redirectResponse($order, $success, $checkUrlWarn = false)
    {
        /**
         *
         * @var Magento\Checkout\Model\Session $checkout
         */
        $checkout = $this->dataHelper->getCheckout();

        // clear all messages in session
        $this->messageManager->getMessages(true);

        $storeId = $order->getStore()->getId();
        if ($this->dataHelper->getCommonConfigData('ctx_mode', $storeId) == 'TEST') {
            // display going to production message
            $message = __('<p><u>GOING INTO PRODUCTION</u></p>You want to know how to put your shop into production mode, please go to this URL : ');
            $message .= '<a href="https://secure.payzen.eu/html/faq/prod" target="_blank">https://secure.payzen.eu/html/faq/prod</a>';
            $this->messageManager->addNotice($message);

            if ($checkUrlWarn) {
                // order not validated by notification URL, in TEST mode, user is webmaster
                // so display a warning about notification URL not working

                if ($this->dataHelper->isMaintenanceMode()) {
                    $message = __('The shop is in maintenance mode.The automatic notification cannot work.');
                } else {
                    $message = __('The automatic validation has not worked. Have you correctly set up the notification URL in your PayZen Back Office ?');
                    $message .= '<br /><br />';
                    $message .= __('For understanding the problem, please read the documentation of the module : <br />&nbsp;&nbsp;&nbsp;- Chapter &laquo;To read carefully before going further&raquo;<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo;Notification URL settings&raquo;');
                }
                $this->messageManager->addError($message);
            }
        }

        /**
         *
         * @var \Magento\Framework\Controller\Result\Redirect $resultRedirect
         */
        $resultRedirect = $this->payzenRedirectFactory->create();
        $resultRedirect->setIframe($this->getRequest()
            ->getParam('iframe', false));

        if ($success) {
            $checkout->setLastQuoteId($order->getQuoteId())
                ->setLastSuccessQuoteId($order->getQuoteId())
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            $this->dataHelper->log('Redirecting to one page checkout success page.');
            $resultRedirect->setPath('checkout/onepage/success', [
                '_scope' => $storeId
            ]);
        } else {
            $this->messageManager->addWarning(__('Checkout and order have been canceled.'));

            $this->dataHelper->log("Restore cart for order #{$order->getId()} to allow re-order quicker.");
            $quote = $this->quoteRepository->get($order->getQuoteId());
            if ($quote->getId()) {
                $quote->setIsActive(true)->setReservedOrderId(null);
                $this->quoteRepository->save($quote);

                $checkout->replaceQuote($quote);
            }

            $this->dataHelper->log('Redirecting to cart page.');
            $resultRedirect->setPath('checkout/cart', [
                '_scope' => $storeId
            ]);
        }

        return $resultRedirect;
    }
}
