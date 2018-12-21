<?php
/**
 * PayZen V2-Payment Module version 1.9.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
