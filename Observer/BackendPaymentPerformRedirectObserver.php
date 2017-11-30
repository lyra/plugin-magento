<?php
/**
 * PayZen V2-Payment Module version 2.1.3 for Magento 2.x. Support contact : support@payzen.eu.
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
     * Redirect to payment platform after backend order creation.
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
            // not interested in this action
            return $this;
        }

        if (! $this->dataHelper->isBackend()) {
            // order placed on frontend
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
