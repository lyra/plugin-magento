<?php
/**
 * PayZen V2-Payment Module version 2.1.1 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2016 Lyra Network and contributors
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend;

class CustgroupOptions extends \Lyranetwork\Payzen\Model\System\Config\Backend\Serialized\ArraySerialized\ConfigArraySerialized
{
    public function beforeSave()
    {
        $values = $this->getValue();

        if (!is_array($values) || empty($values)) {
            $this->setValue([]);
        } else {
            $i = 0;
            foreach ($values as $key => $value) {
                $i++;

                if (empty($value)) {
                    continue;
                }

                if (!empty($value['amount_min']) && (!is_numeric($value['amount_min']) || $value['amount_min'] < 0)) {
                    $this->throwException('Minimum amount', $i);
                } elseif (!empty($value['amount_max']) &&
                    (!is_numeric($value['amount_max']) || $value['amount_max'] < 0)) {
                    $this->throwException('Maximum amount', $i);
                }
            }
        }

        return parent::beforeSave();
    }
}
