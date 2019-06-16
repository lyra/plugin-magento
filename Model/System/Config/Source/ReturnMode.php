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

class ReturnMode implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            [
                'value' => 'GET',
                'label' => __('GET (parameters in the URL)')
            ],
            [
                'value' => 'POST',
                'label' => __('POST (form)')
            ]
        ];
    }
}
