<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Source;

class CardInfoMode implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => '1',
                'label' => __('Bank data acquisition on payment gateway')
            ],
            [
                'value' => '2',
                'label' => __('Card type selection on merchant site')
            ],
            [
                'value' => '5',
                'label' => __('Embedded payment fields on merchant site (REST API)')
            ],
            [
               'value' => '6',
               'label' => __('Embedded payment fields extended on merchant site with logos (REST API)')
            ],
            [
                'value' => '7',
                'label' => __('Embedded payment fields extended on merchant site without logos (REST API)')
            ]
         ];
    }
}
