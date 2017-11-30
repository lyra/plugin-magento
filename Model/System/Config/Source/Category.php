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
