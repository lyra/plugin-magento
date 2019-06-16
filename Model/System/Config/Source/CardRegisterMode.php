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

class CardRegisterMode implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            [
                'value' => '1',
                'label' => __('Registration off by default')
            ],
            [
                'value' => '2',
                'label' => __('Registration on by default')
            ],
            [
                'value' => '3',
                'label' => __('Registration always on')
            ]
        ];
    }
}
