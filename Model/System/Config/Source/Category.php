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

class Category implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray($noSelectOpt = false)
    {
        $options = [
            [
                'value' => 'FOOD_AND_GROCERY',
                'label' => __('Food and grocery')
            ],
            [
                'value' => 'AUTOMOTIVE',
                'label' => __('Automotive')
            ],
            [
                'value' => 'ENTERTAINMENT',
                'label' => __('Entertainment')
            ],
            [
                'value' => 'HOME_AND_GARDEN',
                'label' => __('Home and garden')
            ],
            [
                'value' => 'HOME_APPLIANCE',
                'label' => __('Home appliance')
            ],
            [
                'value' => 'AUCTION_AND_GROUP_BUYING',
                'label' => __('Auction and group buying')
            ],
            [
                'value' => 'FLOWERS_AND_GIFTS',
                'label' => __('Flowers and gifts')
            ],
            [
                'value' => 'COMPUTER_AND_SOFTWARE',
                'label' => __('Computer and software')
            ],
            [
                'value' => 'HEALTH_AND_BEAUTY',
                'label' => __('Health and beauty')
            ],
            [
                'value' => 'SERVICE_FOR_INDIVIDUAL',
                'label' => __('Service for individual')
            ],
            [
                'value' => 'SERVICE_FOR_BUSINESS',
                'label' => __('Service for business')
            ],
            [
                'value' => 'SPORTS',
                'label' => __('Sports')
            ],
            [
                'value' => 'CLOTHING_AND_ACCESSORIES',
                'label' => __('Clothing and accessories')
            ],
            [
                'value' => 'TRAVEL',
                'label' => __('Travel')
            ],
            [
                'value' => 'HOME_AUDIO_PHOTO_VIDEO',
                'label' => __('Home audio, photo, video')
            ],
            [
                'value' => 'TELEPHONY',
                'label' => __('Telephony')
            ]
        ];

        if (! $noSelectOpt) {
            array_unshift($options, [
                'value' => 'CUSTOM_MAPPING',
                'label' => __('(Use category mapping below)')
            ]);
        }

        return $options;
    }
}
