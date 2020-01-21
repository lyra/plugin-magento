<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Standard;

/**
 * Custom renderer for the REST placeholders field.
 */
class RestPlaceholders extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{

    /**
     *
     * @var bool
     */
    protected $staticTable = true;

    /**
     *
     * @var array
     */
    protected $_default = [];

    /**
     * Prepare to render.
     *
     * @return void
     */
    public function _prepareToRender()
    {
        $this->addColumn(
            'field',
            [
                'label' => __('Field'),
                'style' => 'width: 200px;',
                'renderer' => $this->getLabelRenderer('_field')
            ]
        );
        $this->addColumn(
            'placeholder',
            [
                'label' => __('Placeholder'),
                'style' => 'width: 250px;'
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
        $defaultOptions = [
            'pan' => [
                'code' => 'pan',
                'field' => __('Card Number'),
                'placeholder' => ''
            ],
            'expiry' => [
                'code' => 'expiry',
                'field' => __('Expiration Date'),
                'placeholder' => ''
            ],
            'cvv' => [
                'code' => 'cvv',
                'field' => __('CVV'),
                'placeholder' => ''
            ]
        ];

        $savedOptions = $this->getElement()->getValue();
        if (! is_array($savedOptions)) {
            $savedOptions = [];
        }

        $options = [];

        foreach ($defaultOptions as $code => $defaultOption) {
            $option = $defaultOption;
            if (isset($savedOptions[$code])) {
                $option['placeholder'] = $savedOptions[$code]['placeholder'];
            }

            $options[$code] = $option;
        }

        $this->getElement()->setValue($options);
        return parent::getArrayRows();
    }
}
