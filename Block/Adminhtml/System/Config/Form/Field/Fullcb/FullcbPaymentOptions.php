<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Fullcb;

/**
 * Custom renderer for the Full CB payment options field.
 */
class FullcbPaymentOptions extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{
    protected $staticTable = true;

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
                'label' => __('Label'),
                'style' => 'width: 220px;',
            ]
        );
        $this->addColumn(
            'amount_min',
            [
                'label' => __('Min. amount'),
                'style' => 'width: 120px;'
            ]
        );
        $this->addColumn(
            'amount_max',
            [
                'label' => __('Max. amount'),
                'style' => 'width: 120px;'
            ]
        );
        $this->addColumn(
            'rate',
            [
                'label' => __('Rate'),
                'style' => 'width: 100px;'
            ]
        );
        $this->addColumn(
            'cap',
            [
                'label' => __('Cap'),
                'style' => 'width: 100px;'
            ]
        );

        parent::_prepareToRender();
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
            'FULLCB3X' => [
                'label' => sprintf(__('Payment in %s times'), '3'),
                'rate'  => '1.4',
                'cap'   => '9'
            ],
            'FULLCB4X' => [
                'label' => sprintf(__('Payment in %s times'), '4'),
                'rate'  => '2.1',
                'cap'   => '12'
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
                'amount_min' => '',
                'amount_max' => '',
                'rate' => $defaultOption['rate'],
                'cap' => $defaultOption['cap']
            ];

            $savedOptions[$code] = $option;
        }

        $this->getElement()->setValue($savedOptions);
        return parent::getArrayRows();
    }
}
