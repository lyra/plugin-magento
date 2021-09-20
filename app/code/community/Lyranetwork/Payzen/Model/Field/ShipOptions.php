<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_ShipOptions extends Lyranetwork_Payzen_Model_Field_Array
{
    protected $_eventPrefix = 'payzen_field_ship_options';

    protected function _beforeSave()
    {
        $deliveryCompanyRegex = "#^[A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ /'-]{1,127}$#ui";
        $values = $this->getValue();

        if (! is_array($values) || empty($values)) {
            $this->setValue(array());
        } else {
            $i = 0;
            foreach ($values as $id => $value) {
                $i++;

                if (empty($value)) {
                    continue;
                }

                if (! isset($value['type']) || empty($value['type'])
                    || ! isset($value['speed']) || empty($value['speed'])) {
                    unset($values[$id]);
                    continue;
                }
            }

            $this->setValue($values);
        }

        return parent::_beforeSave();
    }
}
