<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;

class Cancel extends AbstractAction
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param \Lyranetwork\Payzen\Helper\Payment $paymentHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        \Lyranetwork\Payzen\Helper\Payment $paymentHelper
    ) {
        $this->paymentHelper = $paymentHelper;

        parent::__construct($context, $customerSession);
    }

    public function execute()
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $customerId = $this->customerSession->getCustomer()->getId();
        $attribute = $this->getRequest()->getPost('alias_attr', false);
        $maskedAttribute = $this->getRequest()->getPost('pm_attr', false);

        if ($customerId && $attribute && $maskedAttribute) {
            if ($this->paymentHelper->deleteIdentifier($customerId, $attribute, $maskedAttribute)) {
                $this->messageManager->addSuccessMessage(__('The stored means of payment was successfully deleted.'));
            } else {
                $this->messageManager->addErrorMessage(__('The stored means of payment could not be deleted.'));
            }
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/index', ['_secure' => true]);
        return $resultRedirect;
    }
}
