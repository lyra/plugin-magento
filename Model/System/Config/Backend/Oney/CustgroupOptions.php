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

class CustgroupOptions extends \Lyranetwork\Payzen\Model\System\Config\Backend\CustgroupOptions
{

    public function beforeSave()
    {
        $values = $this->getValue();
        $config = $this->getFieldConfig();

        $data = $this->getGroups('payzen_oney'); // get data of FacilyPay Oney config group
        if ($data['fields']['active']['value']) { // FacilyPay Oney is activated
            foreach ($values as $key => $value) {
                if (empty($value) || ($value['code'] !== 'all')) {
                    continue;
                }

                if (empty($value['amount_min'])) {
                    $field = 'Minimum amount';
                } elseif (empty($value['amount_max'])) {
                    $field = 'Maximum amount';
                }

                if (isset($field)) {
                    $field = __($field)->render(); // translate field name
                    $group = $this->dataHelper->getGroupTitle($config['path']);
                    $msg = __(
                        'Please enter a value for &laquo;ALL GROUPS - %1&raquo; in &laquo;%2&raquo; section as agreed with Banque Accord.',
                        $field,
                        $group
                    );

                    // throw exception
                    throw new \Magento\Framework\Exception\LocalizedException($msg);
                }
            }
        }

        return parent::beforeSave();
    }
}
