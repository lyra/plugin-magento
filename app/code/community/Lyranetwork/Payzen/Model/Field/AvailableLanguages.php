<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_AvailableLanguages extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $value = $this->getValue();

        if (in_array("", $value)) {
            $this->setValue(array());
        }

        return parent::save();
    }
}
