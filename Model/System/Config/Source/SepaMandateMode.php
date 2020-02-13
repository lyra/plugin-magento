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

class SepaMandateMode implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            [
                'value' => 'PAYMENT',
                'label' => __('One-off SEPA direct debit')
            ],
            [
                'value' => 'REGISTER_PAY',
               'label' => __('Register a recurrent SEPA mandate with direct debit')
            ],
            [
                'value' => 'REGISTER',
                'label' => __('Register a recurrent SEPA mandate without direct debit')
            ]
        ];
    }
}