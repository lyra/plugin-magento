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
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Gift;

/**
 * Custom renderer for the PayZen add gift cards field.
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
