<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend\Choozeo;

class ChoozeoPaymentOptions extends \Lyranetwork\Payzen\Model\System\Config\Backend\Serialized\ArraySerialized\ConfigArraySerialized
{
    public function beforeSave()
    {
        $values = $this->getValue();

        if (! is_array($values) || empty($values)) {
            $this->setValue([]);
        } else {
            $i = 0;
            foreach ($values as $value) {
                $i++;

                if (empty($value)) {
                    continue;
                }

                if (isset($value['amount_min'])) {
                    $this->checkAmount($value['amount_min'], 'Minimum amount', $i);
                }

                if (isset($value['amount_max'])) {
                    $this->checkAmount($value['amount_max'], 'Maximum amount', $i);
                }
            }
        }

        return parent::beforeSave();
    }
}
