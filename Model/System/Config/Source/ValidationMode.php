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
namespace Lyranetwork\Payzen\Model\System\Config\Source;

class ValidationMode extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        $options = [
            [
                'value' => '',
                'label' => __('Back Office configuration')
            ],
            [
                'value' => '0',
                'label' => __('Automatic')
            ],
            [
                'value' => '1',
                'label' => __('Manual')
            ]
        ];

        if (stripos($this->getPath(), '/payzen_general/') === false) {
            array_unshift($options, [
                'value' => '-1',
                'label' => __('PayZen general configuration')
            ]);
        }

        return $options;
    }
}
