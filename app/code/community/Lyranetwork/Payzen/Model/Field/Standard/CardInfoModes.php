<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_Standard_CardInfoModes extends Mage_Core_Model_Config_Data
{
    protected $_message;

    public function save()
    {
        $value = $this->getValue();

        if (($value == 3 || $value == 4) && ! $this->_isFrontSecure()) { // IFRAME mode or REST API.
            $this->_message = Mage::helper('payzen')->__('The iframe mode and the embedded payment fields cannot be used without enabling SSL.');
            $this->setValue(1);
        }

        return parent::save();
    }

    public function afterCommitCallback()
    {
        if ($this->_message) {
            Mage::throwException($this->_message);
        }

        return parent::afterCommitCallback();
    }

    protected function _isFrontSecure()
    {
        if ($this->getScope() === 'websites') {
            $website = Mage::app()->getWebsite($this->getScopeId());
            $flag = $website->getConfig(Mage_Core_Model_Url::XML_PATH_SECURE_IN_FRONT);
        } else {
            $flag = Mage::getStoreConfigFlag(Mage_Core_Model_Url::XML_PATH_SECURE_IN_FRONT, $this->getScopeId());
        }

        return ! empty($flag) && 'false' !== $flag;
    }
}
