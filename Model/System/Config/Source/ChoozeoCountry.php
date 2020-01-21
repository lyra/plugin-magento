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

class ChoozeoCountry implements \Magento\Framework\Option\ArrayInterface
{
    // France and DOM-TOM.
    public static $availableCountries = ['FR', 'GP', 'MQ', 'GF', 'RE', 'YT'];

    /**
     * Locale model
     *
     * @var \Magento\Framework\Locale\ListsInterface
     */
    protected $localeLists;

    /**
     *
     * @param \Magento\Framework\Locale\ListsInterface $localeLists
     */
    public function __construct(\Magento\Framework\Locale\ListsInterface $localeLists)
    {
        $this->localeLists = $localeLists;
    }

    public function toOptionArray()
    {
        $result = [];

        foreach (self::$availableCountries as $code) {
            $name = (string) $this->localeLists->getCountryTranslation($code);
            if (empty($name)) {
                $name = $code;
            }

            $result[] = [
                'value' => $code,
                'label' => $name
            ];
        }

        return $result;
    }
}
