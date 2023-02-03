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

use Magento\Framework\DataObject;

class Token extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\CheckoutAgreements\Model\AgreementsValidator
     */
    protected $agreementsValidator;

    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Lyranetwork\Payzen\Model\Method\Standard
     */
    protected $standardMethod;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \\Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    const REFRESH_TOKEN = 'refresh_token';
    const SET_TOKEN = 'set_token';
    const RESTORE_CART = 'restore_cart';

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @para \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Model\Method\Standard $standardMethod
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Model\Method\Standard $standardMethod,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory
        ) {
            $this->formKeyValidator = $formKeyValidator;
            $this->dataHelper = $dataHelper;
            $this->standardMethod = $standardMethod;
            $this->quoteRepository = $quoteRepository;
            $this->resultJsonFactory = $resultJsonFactory;
            $this->orderFactory = $orderFactory;

            parent::__construct($context);
    }

    public function execute()
    {
        if (! $this->formKeyValidator->validate($this->getRequest()) || $this->expireAjax()) {
            $result = $this->resultJsonFactory->create();
            $result->setStatusHeader(401, '1.1', 'Session expired');
            return $result;
        }

        $token = null;
        $checkout = $this->dataHelper->getCheckout();
        $action = $this->getRequest()->getParam('payzen_action');

        switch ($action) {
            case self::REFRESH_TOKEN:
                // Refresh token from quote data.
                $quote = $this->dataHelper->getCheckoutQuote();
                $this->dataHelper->log("Updating form token for quote #{$quote->getId()}.");

                $token = $this->standardMethod->getRestApiFormToken(true);
                if ($token) {
                    $this->dataHelper->log("Form token updated for quote #{$quote->getId()}.");
                }

                break;

            case self::SET_TOKEN:
                // Create token from order data.
                $lastIncrementId = $this->dataHelper->getCheckout()->getLastRealOrderId();

                $checkout->setData('payzen_last_real_id', $lastIncrementId);
                $this->dataHelper->log('Saving last real order ID in session: '. $lastIncrementId);

                $order = $this->orderFactory->create();
                $order->loadByIncrementId($lastIncrementId);

                $token = $this->standardMethod->getTokenForOrder($order);
                break;

            case self::RESTORE_CART:
                $lastIncrementId = $checkout->getData('payzen_last_real_id');
                if (! $lastIncrementId) {
                    return;
                }

                $order = $this->orderFactory->create();
                $order->loadByIncrementId($lastIncrementId);
                $quote = $this->quoteRepository->get($order->getQuoteId());

                if ($quote->getId() && ! $quote->getIsActive() && ($this->standardMethod->getConfigData('rest_attempts') !== '0')) {
                    $checkout->setData('payzen_last_real_id', null);

                    $this->dataHelper->log("Restore cart for order #{$order->getIncrementId()} to allow more payment attempts.");
                    $quote->setIsActive(true)->setReservedOrderId(null);
                    $this->quoteRepository->save($quote);

                    // To comply with Magento\Checkout\Model\Session::restoreQuote() method.
                    $checkout->replaceQuote($quote)->unsLastRealOrderId();
                    $this->_eventManager->dispatch('restore_quote', ['order' => $order, 'quote' => $quote]);
                }

                return;

            default;
               $token = null;
               break;
        }

        if (! $token) {
            return $this->ajaxErrorResponse();
        }

        $data = new DataObject();
        $data->setData('success', true);
        $data->setData('token', $token);

        return $this->resultJsonFactory->create()->setData($data->getData());
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function ajaxErrorResponse($message = null)
    {
        $result = $this->resultJsonFactory->create();
        if ($message) {
            $result->setStatusHeader(400, '1.1', 'Bad request');

        } else {
            $result->setStatusHeader(500, '1.1', 'Internal server error');
        }

        $data = new DataObject();
        $data->setData('message', $message ? $message : __('An error has occurred during the payment process.'));

        return $result->setData($data->getData());
    }

    /**
     * Validate ajax request.
     *
     * @return bool
     */
    protected function expireAjax()
    {
        if ($this->getRequest()->getParam('payzen_action') == self::REFRESH_TOKEN) {
            $quote = $this->dataHelper->getCheckoutQuote();
            if (! $quote->hasItems() || $quote->getHasError() || ! $quote->validateMinimumAmount()) {
                return true;
            }
        }

        return false;
    }
}
