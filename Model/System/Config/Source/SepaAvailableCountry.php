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

class SepaAvailableCountry implements \Magento\Framework\Option\ArrayInterface
{
    protected $translate;

    public function __construct(
        \Magento\Framework\Locale\TranslatedLists $translate
    ) {
        $this->translate = $translate;
    }

    public function toOptionArray()
    {
        $sepaCountries = \Lyranetwork\Payzen\Model\Api\Form\Api::getSepaCountries();
        $result = [];

        foreach ($sepaCountries as $code) {
            $name = $this->translate->getCountryTranslation($code);

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
