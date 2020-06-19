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

class MultiPaymentCard implements \Magento\Framework\Option\ArrayInterface
{
    protected $multiCards = [
        'AMEX',
        'CB',
        'DINERS',
        'DISCOVER',
        'E-CARTEBLEUE',
        'JCB',
        'MASTERCARD',
        'PRV_BDP',
        'PRV_BDT',
        'PRV_OPT',
        'PRV_SOC',
        'VISA',
        'VISA_ELECTRON',
        'VPAY'
    ];

    public function toOptionArray()
    {
        $options = [
            [
                'value' => '',
                'label' => __('ALL')
            ]
        ];

        foreach (\Lyranetwork\Payzen\Model\Api\PayzenApi::getSupportedCardTypes() as $code => $name) {
            if (in_array($code, $this->multiCards)) {
                $options[] = [
                    'value' => $code,
                    'label' => $name
                ];
            }
        }

        return $options;
    }
}
