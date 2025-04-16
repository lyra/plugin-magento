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

use Lyranetwork\Payzen\Model\Api\Form\Api as PayzenApi;
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
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    const REFRESH_TOKEN = 'refresh_token';
    const SET_TOKEN = 'set_token';
    const RESTORE_CART = 'restore_cart';
    const GET_TOKEN_AMOUNT_IN_CENTS = 'get_token_amount_in_cents';
    const LOG_SRC = 'log_src';

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @para \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Model\Method\Standard $standardMethod
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Model\Method\Standard $standardMethod,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
            $this->formKeyValidator = $formKeyValidator;
            $this->dataHelper = $dataHelper;
            $this->standardMethod = $standardMethod;
            $this->quoteRepository = $quoteRepository;
            $this->resultJsonFactory = $resultJsonFactory;

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
                $lastIncrementId = $checkout->getData(\Lyranetwork\Payzen\Helper\Data::LAST_REAL_ID);
                if ($lastIncrementId) {
                    $order = $this->dataHelper->getOrderByIncrementId($lastIncrementId);
                } else {
                    $orderId = $this->dataHelper->getCheckout()->getLastOrderId();
                    $order = $this->dataHelper->getOrderById($orderId);
                }

                if (! $order) {
                    $this->dataHelper->log("No order to pay.");
                    break;
                }

                $token = $this->standardMethod->getTokenForOrder($order);
                break;

            case self::RESTORE_CART:
                $lastIncrementId = $checkout->getData(\Lyranetwork\Payzen\Helper\Data::LAST_REAL_ID);
                if (! $lastIncrementId) {
                    return;
                }

                $order = $this->dataHelper->getOrderByIncrementId($lastIncrementId);
                $quote = $this->quoteRepository->get($order->getQuoteId());

                if ($quote->getId() && ! $quote->getIsActive() && ($this->standardMethod->getConfigData('rest_attempts') !== '0')) {
                    $checkout->setData(\Lyranetwork\Payzen\Helper\Data::LAST_REAL_ID, null);

                    $this->dataHelper->log("Restore cart for order #{$order->getIncrementId()} to allow more payment attempts.");
                    $quote->setIsActive(true)->setReservedOrderId(null);
                    $this->quoteRepository->save($quote);

                    // To comply with Magento\Checkout\Model\Session::restoreQuote() method.
                    $checkout->replaceQuote($quote)->unsLastRealOrderId();
                    $this->_eventManager->dispatch('restore_quote', ['order' => $order, 'quote' => $quote]);
                }

                return;

            case self::GET_TOKEN_AMOUNT_IN_CENTS:
                // Create token from order data.
                $amount = $this->getRequest()->getParam('displayAmount');
                $currencyCode = $this->getRequest()->getParam('displayCurrency');
                $currency = $currencyCode ? PayzenApi::findCurrencyByAlphaCode($currencyCode) : null;
                if (($this->dataHelper->getCommonConfigData('online_transactions_currency') == '2') || (($this->dataHelper->getCommonConfigData('online_transactions_currency') !== '2') && ! $currency)) {
                    $currencyCode = $this->getRequest()->getParam('baseCurrency');
                    $currency = $currencyCode ? PayzenApi::findCurrencyByAlphaCode($currencyCode) : null;
                    $amount = $this->getRequest()->getParam('baseAmount');
                }

                if ($amount && $currency) {
                    $amountInCents = $currency->convertAmountToInteger($amount);

                    $data = new DataObject();
                    $data->setData('amountincents', $amountInCents);

                    return $this->resultJsonFactory->create()->setData($data->getData());
                }

                break;

            case self::LOG_SRC:
                $src = $this->getRequest()->getParam('payzen_src');
                $this->dataHelper->log("PlaceOrderClick() function has been called from source [{$src}].");

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
