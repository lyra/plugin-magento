<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend\Oney;

use Lyranetwork\Payzen\Model\System\Config\Backend\Serialized\ArraySerialized\ConfigArraySerialized;

class OneyPaymentOptions extends ConfigArraySerialized
{
    private function isEmptyOneyOptions ($values)
    {
        if (! is_array($values) || empty($values)) {
            return true;
        }

        if ((count($values) === 1) && isset($values['__empty'])) {
            return true;
        }

        return false;
    }

    public function beforeSave()
    {
        $values = $this->getValue();
        if ($this->isEmptyOneyOptions($values)) {
            if (isset($_SESSION['payzen_oney_enabled']) && $_SESSION['payzen_oney_enabled'] === 'True') {
                unset($_SESSION['payzen_oney_enabled']);

                $config = $this->getFieldConfig();

                $field = __($config['label'])->render();
                $group = $this->dataHelper->getGroupTitle($config['path']);

                $msg = __('The field &laquo; %1 &raquo; is required for section &laquo; %2 &raquo;.', $field, $group)->render();
                throw new \Magento\Framework\Exception\LocalizedException(__($msg));
            } else {
                $this->setValue([]);
            }
        } else {
            $i = 0;
            foreach ($values as $value) {
                $i++;

                if (empty($value)) {
                    continue;
                }

                if (! preg_match('#^.{0,64}$#u', $value['label'])) {
                    $this->throwException('Label', $i);
                }

                if (empty($value['code'])) {
                    $this->throwException('Code', $i);
                }

                if (isset($value['minimum'])) {
                    $this->checkAmount($value['minimum'], 'Min. amount', $i);
                }

                if (isset($value['maximum'])) {
                    $this->checkAmount($value['maximum'], 'Max. amount', $i);
                }

                $this->checkMandatoryDecimal($value['count'], 'Count', $i);

                if (isset($value['rate'])) {
                    $this->checkRate($value['rate'], 'Rate', $i);
                }
            }
        }

        return parent::beforeSave();
    }
}
