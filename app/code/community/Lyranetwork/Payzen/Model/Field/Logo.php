<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_Logo extends Mage_Adminhtml_Model_System_Config_Backend_Image
{
    protected function _beforeSave()
    {
        $value = $this->getValue();
        $delete = is_array($value) && ! empty($value['delete']); // True if logo will be deleted.

        parent::_beforeSave();

        if (! $_FILES['groups']['tmp_name'][$this->getGroupId()]['fields'][$this->getField()]['value']) {
            // Save the default value.
            if (! $this->hasData('value') && ! $delete) {
                $this->setValue($this->getOldValue());
            }
        }

        return $this;
    }
}
