<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Franfinance;

/**
 * Custom renderer for the Franfinance payment options field.
 */
class FranfinancePaymentOptions extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{
    /**
     * Prepare to render.
     *
     * @return void
     */
    public function _prepareToRender()
    {
        $this->addColumn(
            'label',
            [
                'label' => __('Label '),
                'style' => 'width: 220px;',
            ]
        );
        $this->addColumn(
            'payment_means',
            [
                'label' => __('Count '),
                'style' => 'width: 70px;',
                'renderer' => $this->getListRenderer('payment_means', $this->getPaymentMeans())
            ]
        );
        $this->addColumn(
            'fees',
            [
                'label' => __('Fees'),
                'style' => 'width: 120px;',
                'renderer' => $this->getListRenderer('fees', $this->getFeeOptions())
            ]
        );
        $this->addColumn(
            'amount_min',
            [
                'label' => __('Min. amount'),
                'style' => 'width: 100px;'
            ]
        );
        $this->addColumn(
            'amount_max',
            [
                'label' => __('Max. amount'),
                'style' => 'width: 100px;'
            ]
        );

        parent::_prepareToRender();
    }

    public function getPaymentMeans()
    {
        /** @var array[string][string] $options */
        $options = [
            'FRANFINANCE_3X' => '3x',
            'FRANFINANCE_4X' => '4x'
        ];

        return $options;
    }

    public function getFeeOptions()
    {
        /** @var array[string][string] $options */
        $options = [
            '0' => __('Without fees'),
            '1' => __('With fees')
        ];

        return $options;
    }

    /**
     * Obtain existing data from form element.
     *
     * Each row will be instance of Varien_Object
     *
     * @return array
     */
    public function getArrayRows()
    {
        /** @var array[string][array] $defaultOptions */
        $defaultOptions = [
            'FRANFINANCE_3X' => [
                'label'         => sprintf(__('Payment in %s times'), '3'),
                'amount_max'    => '3000'
            ],
            'FRANFINANCE_4X' => [
                'label'         => sprintf(__('Payment in %s times'), '4'),
                'amount_max'    => '4000'
            ]
        ];

        $savedOptions = $this->getElement()->getValue();
        if (! is_array($savedOptions)) {
            $savedOptions = [];
        }

        foreach ($savedOptions as $code => $option) {
            if (key_exists($code, $defaultOptions)) {
                unset($defaultOptions[$code]);
            }
        }

        // Add not saved yet options.
        foreach ($defaultOptions as $code => $defaultOption) {
            $option = [
                'label' => $defaultOption['label'],
                'payment_means' => $code,
                'fees' => '0',
                'amount_min' => '100',
                'amount_max' => $defaultOption['amount_max'],
            ];

            $savedOptions[$code] = $option;
        }

        $this->getElement()->setValue($savedOptions);
        return parent::getArrayRows();
    }
}
