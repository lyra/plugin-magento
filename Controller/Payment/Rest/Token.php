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
     * @var \Lyranetwork\Payzen\Helper\Rest
     */
    protected $restHelper;

    /**
     * @var \Lyranetwork\Payzen\Model\Method\Standard
     */
    protected $standardMethod;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Quote\Model\SubmitQuoteValidator
     */
    protected $submitQuoteValidator;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
     * @param \Magento\CheckoutAgreements\Model\AgreementsValidator $agreementsValidator
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Rest $restHelper
     * @param \Lyranetwork\Payzen\Model\Method\Standard $standardMethod
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Quote\Model\SubmitQuoteValidator $submitQuoteValidator
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\CheckoutAgreements\Model\AgreementsValidator $agreementsValidator,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Rest $restHelper,
        \Lyranetwork\Payzen\Model\Method\Standard $standardMethod,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Model\SubmitQuoteValidator $submitQuoteValidator
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->agreementsValidator = $agreementsValidator;
        $this->dataHelper = $dataHelper;
        $this->restHelper = $restHelper;
        $this->standardMethod = $standardMethod;
        $this->checkoutHelper = $checkoutHelper;
        $this->customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->submitQuoteValidator = $submitQuoteValidator;

        parent::__construct($context);
    }

    public function execute()
    {
        if (! $this->formKeyValidator->validate($this->getRequest()) || $this->expireAjax()) {
            $result = $this->resultJsonFactory->create();
            $result->setStatusHeader(401, '1.1', 'Session expired');

            return $result;
        }

        $quote = $this->dataHelper->getCheckoutQuote();
        $this->setCheckoutMethod($quote);

        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        try {
            $this->submitQuoteValidator->validateQuote($quote);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->ajaxErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->ajaxErrorResponse();
        }

        $this->dataHelper->log("Updating form token for quote #{$quote->getId()}, reserved order ID: #{$quote->getReservedOrderId()}.");

        $token = $this->standardMethod->getRestApiFormToken();
        if (! $token) {
            return $this->ajaxErrorResponse();
        }

        $data = new DataObject();
        $data->setData('success', true);
        $data->setData('token', $token);

        $this->dataHelper->log("Form token updated for quote #{$quote->getId()}, reserved order ID: #{$quote->getReservedOrderId()}.");

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
        $quote = $this->dataHelper->getCheckoutQuote();
        if (! $quote->hasItems() || $quote->getHasError() || ! $quote->validateMinimumAmount()) {
            return true;
        }

        return false;
    }

    private function setCheckoutMethod($quote)
    {
        if ($quote->getCheckoutMethod()) {
            return;
        }

        if ($this->customerSession->isLoggedIn()) {
            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
        } elseif ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
        } else {
            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
        }
    }
}
