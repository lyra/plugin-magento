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

class AvailableLanguages extends \Magento\Framework\App\Config\Value
{
    public function save()
    {
        $value = $this->getValue();

        if (in_array('', $value)) {
            $this->setValue([]);
        }

        return parent::save();
    }
}
