<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Source_Choozeo_AvailableCountries
{
    // France and DOM-TOM.
    protected $_availableCountries = array('FR', 'GP', 'MQ', 'GF', 'RE', 'YT');

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
