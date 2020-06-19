<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend;

class Logo extends \Magento\Config\Model\Config\Backend\Image
{
    public function beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value) && ! empty($value['name'])) {
            $value['value'] = $value['name'];
            $this->setValue($value);
        }

        parent::beforeSave();

        // Recover the last saved value.
        if (! $this->getValue() && empty($value['delete'])) {
            $this->setValue($this->getOldValue());
        }

        return $this;
    }
}
