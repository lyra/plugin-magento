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
namespace Lyranetwork\Payzen\Model\System\Config\Backend\Oney;

use Lyranetwork\Payzen\Model\System\Config\Backend\Serialized\ArraySerialized\ConfigArraySerialized;

class OneyPaymentOptions extends ConfigArraySerialized
{

    public function beforeSave()
    {
        $values = $this->getValue();

        if (! is_array($values) || empty($values)) {
            $this->setValue([]);
        } else {
            $i = 0;
            foreach ($values as $key => $value) {
                $i ++;

                if (empty($value)) {
                    continue;
                }

                if (! preg_match('#^.{0,64}$#', $value['label'])) {
                    $this->throwException('Label', $i);
                }
                if (empty($value['code'])) {
                    $this->throwException('Code', $i);
                }
                if (! empty($value['minimum']) && ! preg_match('#^\d+(\.\d+)?$#', $value['minimum'])) {
                    $this->throwException('Min. amount', $i);
                }
                if (! empty($value['maximum']) && ! preg_match('#^\d+(\.\d+)?$#', $value['maximum'])) {
                    $this->throwException('Max. amount', $i);
                }
                if (! preg_match('#^[1-9]\d*$#', $value['count'])) {
                    $this->throwException('Count', $i);
                }
                if (! is_numeric($value['rate']) || $value['rate'] >= 100 || $value['rate'] < 0) {
                    $this->throwException('Rate', $i);
                }
            }
        }

        return parent::beforeSave();
    }
}
