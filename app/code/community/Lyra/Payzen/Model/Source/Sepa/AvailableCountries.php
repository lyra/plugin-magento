<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyra_Payzen_Model_Source_Sepa_AvailableCountries
{
    protected $_availableCountries = array(
        'FI', 'AT', 'PT', 'BE', 'BG', 'ES', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FR', 'GF', 'DE', 'GI', 'GR',
        'GP', 'HU', 'IS', 'IE', 'LV', 'LI', 'LT', 'LU', 'PT', 'MT', 'MQ', 'YT', 'MC', 'NL', 'NO', 'PL',
        'RE', 'RO', 'BL', 'MF', 'PM', 'SM', 'SK', 'SE', 'CH', 'GB'
    );

    public function toOptionArray()
    {
        $result = array();
        foreach ($this->_availableCountries as $code) {
            $name = Mage::app()->getLocale()->getCountryTranslation($code);
            if (empty($name)) {
                $name = $code;
            }

            $result[] = array(
                'value' => $code,
                'label' => $name
            );
        }

        return $result;
    }

    public function getCountryCodes()
    {
        return $this->_availableCountries;
    }
}
