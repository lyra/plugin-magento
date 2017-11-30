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
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Multi;

/**
 * Custom renderer for the PayZen multi payment options field.
 */
class MultiPaymentOptions extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
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
                'label' => __('Label') . '<span style="color: red;">*</span>',
                'style' => 'width: 150px;'
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
            'contract',
            [
                'label' => __('Contract'),
                'style' => 'width: 65px;'
            ]
        );
        $this->addColumn(
            'count',
            [
                'label' => __('Count') . '<span style="color: red;">*</span>',
                'style' => 'width: 65px;'
            ]
        );
        $this->addColumn(
            'period',
            [
                'label' => __('Period') . '<span style="color: red;">*</span>',
                'style' => 'width: 65px;'
            ]
        );
        $this->addColumn(
            'first',
            [
                'label' => __('1st payment'),
                'style' => 'width: 70px;'
            ]
        );

        parent::_prepareToRender();
    }
}
