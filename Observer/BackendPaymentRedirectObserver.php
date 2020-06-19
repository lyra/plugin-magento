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

class BackendPaymentRedirectObserver implements ObserverInterface
{
    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     *
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     *
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->dataHelper = $dataHelper;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
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
        $order = $observer->getEvent()->getOrder();
        if (! $order || ! $order->getId()) {
            // Order creation failed.
            return $this;
        }

        if (! $this->dataHelper->isBackend()) {
            // Placed on frontend.
            return $this;
        }

        $method = $order->getPayment()->getMethodInstance();
        if ($method instanceof \Lyranetwork\Payzen\Model\Method\Payzen) {
            $flag = false;
            if ($data = $this->request->getPost('order')) {
                $flag = isset($data['send_confirmation']) ? (bool) $data['send_confirmation'] : false;
            }

            if (! $flag) {
                $order->setSendEmail($flag);
                $order->save();
            }

            $redirectUrl = $this->urlBuilder->getUrl(
                'payzen/payment/redirect',
                [
                    'order_id' => $order->getId(),
                    '_secure' => true
                ]
            );

            $this->coreRegistry->register(Constants::REDIRECT_URL, $redirectUrl);
            $this->coreRegistry->register(Constants::LAST_SUCCESS_QUOTE_ID, $order->getQuoteId());
        }

        return $this;
    }
}
