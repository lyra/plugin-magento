<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Oney;

/**
 * Custom renderer for the oney payment options field.
 */
class OneyPaymentOptions extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
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
                'label' => __('Label'),
                'style' => 'width: 150px;'
            ]
        );
        $this->addColumn(
            'code',
            [
                'label' => __('Code'),
                'style' => 'width: 65px;'
            ]
        );
        $this->addColumn(
            'minimum',
            [
                'label' => __('Min. amount'),
                'style' => 'width: 80px;'
            ]
        );
        $this->addColumn(
            'maximum',
            [
                'label' => __('Max. amount'),
                'style' => 'width: 80px;'
            ]
        );
        $this->addColumn(
            'count',
            [
                'label' => __('Count'),
                'style' => 'width: 65px;'
            ]
        );
        $this->addColumn(
            'rate',
            [
                'label' => __('Rate'),
                'style' => 'width: 70px;'
            ]
        );

        parent::_prepareToRender();
    }
}
