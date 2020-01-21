<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Gift;

/**
 * Custom renderer for the add gift cards field.
 */
class AddedCards extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{

    /**
     * Prepare to render.
     *
     * @return void
     */
    public function _prepareToRender()
    {
        $this->addColumn(
            'code',
            [
                'label' => __('Card code'),
                'style' => 'width: 100px;'
            ]
        );
        $this->addColumn(
            'name',
            [
                'label' => __('Card label'),
                'style' => 'width: 180px;'
            ]
        );
        $this->addColumn(
            'logo',
            [
                'label' => __('Card logo'),
                'style' => 'width: 340px;',
                'size' => '20',
                'renderer' => $this->getUploadButtonRenderer('_logo')
            ]
        );

        parent::_prepareToRender();
    }
}
