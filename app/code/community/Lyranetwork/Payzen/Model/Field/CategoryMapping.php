<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_CategoryMapping extends Lyranetwork_Payzen_Model_Field_Array
{
    protected $_eventPrefix = 'payzen_field_category_mapping';

    public function _beforeSave()
    {
        $values = $this->getValue();

        if (! is_array($values) || empty($values)) {
            $this->setValue(array());
        } else {
            foreach ($values as $id => $value) {
                if (empty($value)) {
                    continue;
                }

                if (! isset($value['payzen_category']) || empty($value['payzen_category'])) {
                    unset($values[$id]);
                }
            }

            $this->setValue($values);
        }

        return parent::_beforeSave();
    }
}
