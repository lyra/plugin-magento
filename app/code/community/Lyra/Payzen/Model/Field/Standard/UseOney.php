<?php
/**
 * PayZen V2-Payment Module version 1.8.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
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

class Lyra_Payzen_Model_Field_Standard_UseOney extends Mage_Core_Model_Config_Data
{
    protected $message;

    public function save()
    {
        $this->message = '';

        if ($this->getValue() /* propose FacilyPay Oney in standard payment enabled */) {
            $data = $this->getGroups('payzen_oney'); // get data of FacilyPay Oney config group
            $oneyActive = isset($data['fields']['active']['value']) && $data['fields']['active']['value'];

            if ($oneyActive) {
                $this->setValue(0);
            } else {
                try {
                    // check Oney requirements
                    Mage::helper('payzen/util')->checkOneyRequirements($this->getScope(), $this->getScopeId());
                } catch (Mage_Core_Exception $e) {
                    $this->setValue(0);

                    $this->message = $e->getMessage();
                }
            }
        }

        return parent::save();
    }

    public function afterCommitCallback()
    {
        if (! empty($this->message)) {
            Mage::throwException($this->message . "\n" . Mage::helper('payzen')->__('FacilyPay Oney payment mean cannot be used.'));
        }

        return parent::afterCommitCallback();
    }
}
