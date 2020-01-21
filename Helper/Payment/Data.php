<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Helper\Payment;

use Magento\Framework\View\LayoutFactory;

class Data extends \Magento\Payment\Helper\Data
{

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param LayoutFactory $layoutFactory
     * @param \Magento\Payment\Model\Method\Factory $paymentMethodFactory
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Magento\Framework\App\Config\Initial $initialConfig
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \Lyranetwork\Payzen\Helper\Data $dataHelper
    ) {
        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig
        );

        $this->dataHelper = $dataHelper;
    }

    /**
     * Retrieve all payment methods.
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        $methods = parent::getPaymentMethods();

        $payzenMultiTitle = $methods['payzen_multi']['title']; // Get multi payment general title.
        unset($methods['payzen_multi']);

        // Add multiple payment virtual methods to the list.
        foreach ($this->dataHelper->getMultiPaymentModelConfig() as $config) {
            $code = substr($config['path'], strlen('payment/'), - strlen('/model'));
            $count = substr($code, strlen('payzen_multi_'));

            $methods[$code] = [
                'model' => $config['value'],
                'title' => $payzenMultiTitle . " ($count)",
                'group' => 'payzen'
            ];
        }

        return $methods;
    }
}
