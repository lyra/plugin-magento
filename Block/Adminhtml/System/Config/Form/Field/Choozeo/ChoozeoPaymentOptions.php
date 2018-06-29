<?php
/**
 * PayZen V2-Payment Module version 2.3.0 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Choozeo;

/**
 * Custom renderer for the PayZen multi payment options field.
 */
class ChoozeoPaymentOptions extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
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
                'style' => 'width: 160px;',
                'renderer' => $this->getLabelRenderer('_title')
            ]
        );
        $this->addColumn(
            'amount_min',
            [
                'label' => __('Minimum amount'),
                'style' => 'width: 160px;'
            ]
        );
        $this->addColumn(
            'amount_max',
            [
                'label' => __('Maximum amount'),
                'style' => 'width: 160px;'
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
        /** @var array[string][string] $options */
        $options =[
            'EPNF_3X' => 'Choozeo 3X CB',
            'EPNF_4X' => 'Choozeo 4X CB'
        ];

        $savedOptions = $this->getElement()->getValue();
        if (! is_array($savedOptions)) {
            $savedOptions = [];
        }

        foreach ($savedOptions as $id => $savedOption) {
            if (key_exists($savedOption['code'], $options)) {
                $savedOptions[$id]['label'] = $options[$savedOption['code']];
                unset($options[$savedOption['code']]);
            }
        }

        // add not saved yet groups
        foreach ($options as $code => $label) {
            $option = [
                'code' => $code,
                'label' => $label,
                'amount_min' => '',
                'amount_max' => ''
            ];

            $savedOptions[uniqid('_' . $code . '_')] = $option;
        }

        $this->getElement()->setValue($savedOptions);
        return parent::getArrayRows();
    }
}
