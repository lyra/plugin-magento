<?php
/**
 * PayZen V2-Payment Module version 1.7.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
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
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lyra_Payzen_Model_Field_Standard_CardInfoModes extends Mage_Core_Model_Config_Data
{
    protected $error = false;

    public function save()
    {
        $value = $this->getValue();

        if ($value == 3 && !$this->_isFrontSecure()) {
            $this->error = true;
            $this->setValue(1);
        }

        return parent::save();
    }

    public function afterCommitCallback()
    {
        if ($this->error) {
            Mage::throwException(Mage::helper('payzen')->__('The card data entry on merchant site cannot be used without enabling SSL.'));
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

        return !empty($flag) && 'false' !== $flag;
    }
}
