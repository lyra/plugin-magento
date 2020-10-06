<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field;

/**
 * Custom renderer for the FacilyPay Oney shipping options field.
 */
class ShipOptions extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{
    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     *
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     *
     * @var bool
     */
    protected $staticTable = true;

    /**
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper
     * @param \Magento\Shipping\Model\Config $shippingConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Lyranetwork\Payzen\Helper\Checkout $checkoutHelper,
        \Magento\Shipping\Model\Config $shippingConfig,
        array $data = []
    ) {
        $this->checkoutHelper = $checkoutHelper;
        $this->shippingConfig = $shippingConfig;

        parent::__construct($context, $data);
    }

    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'title',
            [
                'label' => __('Method title'),
                'style' => 'width: 210px;',
                'renderer' => $this->getLabelRenderer('_title')
            ]
        );
        $this->addColumn(
            'oney_label',
            [
                'label' => __('FacilyPay Oney label'),
                'style' => 'width: 210px;'
            ]
        );

        $this->addColumn(
            'type',
            [
                'label' => __('Type'),
                'style' => 'width: 130px;',
                'renderer' => $this->getListRenderer(
                    '_type',
                    [
                        'PACKAGE_DELIVERY_COMPANY' => 'Delivery company',
                        'RECLAIM_IN_SHOP' => 'Reclaim in shop',
                        'RELAY_POINT' => 'Relay point',
                        'RECLAIM_IN_STATION' => 'Reclaim in station'
                    ]
                )
            ]
        );

        $this->addColumn(
            'speed',
            [
                'label' => __('Speed'),
                'style' => 'width: 75px;',
                'renderer' => $this->getListRenderer(
                    '_speed',
                    [
                        'STANDARD' => 'Standard',
                        'EXPRESS' => 'Express'
                    ]
                )
            ]
        );

        parent::_prepareToRender();
    }

    /**
     * Obtain existing data from form element.
     * Each row will be instance of \Magento\Framework\DataObject
     *
     * @return array
     */
    public function getArrayRows()
    {
        $value = [];

        $allMethods = $this->getAllShippingMethods();

        $savedMethods = $this->getElement()->getValue();
        if ($savedMethods && is_array($savedMethods) && ! empty($savedMethods)) {
            foreach ($savedMethods as $id => $method) {
                if (key_exists($method['code'], $allMethods)) {
                    // Update magento method title.
                    $method['title'] = $allMethods[$method['code']];
                    $value[$id] = $method;

                    unset($allMethods[$method['code']]);
                }
            }
        }

        // Add not saved yet methods.
        if ($allMethods && is_array($allMethods) && ! empty($allMethods)) {
            foreach ($allMethods as $code => $name) {
                $value[uniqid('_' . $code . '_')] = [
                    'code' => $code,
                    'title' => $name,
                    'oney_label' => $this->checkoutHelper->cleanShippingMethod($name), // To match Oney restrictions.
                    'type' => 'PACKAGE_DELIVERY_COMPANY',
                    'speed' => 'STANDARD',
                    'mark' => true
                ];
            }
        }

        $this->getElement()->setValue($value);
        return parent::getArrayRows();
    }

    private function getAllShippingMethods()
    {
        $allMethods = [];

        $store = null;
        if ($this->getElement()->getScope() === \Magento\Config\Block\System\Config\Form::SCOPE_STORES) {
            $store = $this->getElement()->getScopeId();
        }

        // List of all configured carriers.
        $carriers = $this->shippingConfig->getAllCarriers($store);

        foreach ($carriers as $carrierCode => $carrierModel) {
            $carrierModel->setStore($store);

            // Filter carriers to get active ones on current scope.
            if (! $carrierModel->isActive()) {
                continue;
            }

            try {
                $carrierMethods = $carrierModel->getAllowedMethods();
                if (! $carrierMethods) {
                    continue;
                }

                $carrierTitle = $carrierModel->getConfigData('title');
                foreach ($carrierMethods as $methodCode => $methodTitle) {
                    $code = $carrierCode . '_' . $methodCode;

                    $title = '[' . $carrierTitle . '] ';
                    if (is_string($methodTitle) && ! empty($methodTitle)) {
                        $title .= $methodTitle;
                    } else { // Non standard method title.
                        $title .= $methodCode;
                    }

                    $allMethods[$code] = $title;
                }
            } catch (\Exception $e) {
                // Just this shipping method.
                continue;
            }
        }

        return $allMethods;
    }
}
