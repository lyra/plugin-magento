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
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
     * @param \Magento\CheckoutAgreements\Model\AgreementsValidator $agreementsValidator
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Lyranetwork\Payzen\Helper\Rest $restHelper
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\CheckoutAgreements\Model\AgreementsValidator $agreementsValidator,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Lyranetwork\Payzen\Helper\Rest $restHelper,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->agreementsValidator = $agreementsValidator;
        $this->dataHelper = $dataHelper;
        $this->restHelper = $restHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        if (! $this->formKeyValidator->validate($this->getRequest()) || $this->expireAjax()) {
            $result = $this->resultJsonFactory->create();
            $result->setStatusHeader(401, '1.1', 'Session Expired');

            $data = new DataObject();
            $data->setData('success', false);

            return $result->setData($data->getData());
        }

        $quote = $this->dataHelper->getCheckoutQuote();
        $this->setCheckoutMethod($quote);

        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        $token = $quote->getPayment()->getMethodInstance()->getRestApiFormToken();

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
    private function ajaxErrorResponse()
    {
        $result = $this->resultJsonFactory->create();
        $result->setStatusHeader(500, '1.1', 'Internal Server Error');

        $data = new DataObject();
        $data->setData('success', false);
        $data->setData('message', __('An error has occurred during the payment process.'));

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
