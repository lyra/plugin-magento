<?php
/**
 * PayZen V2-Payment Module version 2.1.3 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
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
            foreach ($values as $key => $value) {
                $i ++;

                if (empty($value)) {
                    continue;
                }

                if (empty($value['label'])) {
                    $this->throwException('Label', $i);
                }

                $this->checkAmount($value['minimum'], 'Min. amount', $i);
                $this->checkAmount($value['maximum'], 'Max. amount', $i);

                if (! empty($value['contract']) && ! preg_match('#^[^;=]+$#', $value['contract'])) {
                    $this->throwException('Contract', $i);
                }

                $this->checkDecimal($value['count'], 'Count', $i);
                $this->checkDecimal($value['period'], 'Period', $i);

                if (! empty($value['first']) && (! is_numeric($value['first']) || $value['first'] >= 100)) {
                    $this->throwException('1st payment', $i);
                }

                $options[] = $value;
            }

            $this->dataHelper->updateMultiPaymentModelConfig($options);
        }

        return parent::beforeSave();
    }

    private function checkDecimal($value, $fieldLabel, $i)
    {
        if (! preg_match('#^[1-9]\d*$#', $value)) {
            $this->throwException($fieldLabel, $i);
        }
    }

    private function checkAmount($amount, $fieldLabel, $i)
    {
        if (! empty($amount) && ! preg_match('#^\d+(\.\d+)?$#', $amount)) {
            $this->throwException($fieldLabel, $i);
        }
    }
}
