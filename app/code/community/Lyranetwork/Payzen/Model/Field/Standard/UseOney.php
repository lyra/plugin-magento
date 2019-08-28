<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_Standard_UseOney extends Mage_Core_Model_Config_Data
{
    protected $message;

    public function save()
    {
        $this->message = '';

        if ($this->getValue() /* propose FacilyPay Oney in standard payment enabled */) {
            $data = $this->getGroups('payzen_oney'); // Get data of FacilyPay Oney config group.
            $oneyActive = isset($data['fields']['oney_active']['value']) && $data['fields']['oney_active']['value'];

            if ($oneyActive) {
                $this->setValue(0);
            } else {
                try {
                    // Check Oney requirements.
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
            Mage::throwException($this->message . "\n" . Mage::helper('payzen')->__('FacilyPay Oney means of payment cannot be used.'));
        }

        return parent::afterCommitCallback();
    }
}
