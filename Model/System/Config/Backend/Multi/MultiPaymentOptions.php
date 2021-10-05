<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend\Multi;

use Lyranetwork\Payzen\Model\System\Config\Backend\Serialized\ArraySerialized\ConfigArraySerialized;

class MultiPaymentOptions extends ConfigArraySerialized
{
    public function beforeSave()
    {
        $values = $this->getValue();

        if (! is_array($values) || empty($values)) {
            $this->setValue([]);
        } else {
            $i = 0;
            $options = [];
            foreach ($values as $value) {
                $i++;

                if (empty($value)) {
                    continue;
                }

                if (empty($value['label'])) {
                    $this->throwException('Label', $i);
                }

                if (isset($value['minimum'])) {
                    $this->checkAmount($value['minimum'], 'Min. amount', $i);
                }

                if (isset($value['maximum'])) {
                    $this->checkAmount($value['maximum'], 'Max. amount', $i);
                }

                if (! empty($value['contract']) && ! preg_match('#^[^;=]+$#', $value['contract'])) {
                    $this->throwException('Contract', $i);
                }

                $this->checkMandatoryDecimal($value['count'], 'Count', $i);
                $this->checkMandatoryDecimal($value['period'], 'Period', $i);

                if (isset($value['first'])) {
                    $this->checkRate($value['first'], '1st installment', $i);
                }

                $options[] = $value;
            }

            $this->dataHelper->updateMultiPaymentModelConfig($options);
        }

        return parent::beforeSave();
    }
}
