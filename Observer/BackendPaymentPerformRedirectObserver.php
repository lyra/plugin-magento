<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Lyranetwork\Payzen\Block\Constants;

class BackendPaymentPerformRedirectObserver implements ObserverInterface
{
    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     *
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(\Lyranetwork\Payzen\Helper\Data $dataHelper, \Magento\Framework\Registry $coreRegistry)
    {
        $this->dataHelper = $dataHelper;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Redirect to payment gateway after backend order creation.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $response = $observer->getEvent()->getResponse();
        $request = $observer->getEvent()->getRequest();

        $moduleName = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        if ($moduleName !== 'sales' || $controller !== 'order_create' || $action !== 'save') {
            // Not interested in this action.
            return $this;
        }

        if (! $this->dataHelper->isBackend()) {
            // Order placed on frontend.
            return $this;
        }

        $url = $this->coreRegistry->registry(Constants::REDIRECT_URL);
        if ($url) {
            $this->dataHelper->getCheckout()->setLastSuccessQuoteId(
                $this->coreRegistry->registry(Constants::LAST_SUCCESS_QUOTE_ID)
            );

            $response->setRedirect($url)->sendResponse();
        }

        return $this;
    }
}
