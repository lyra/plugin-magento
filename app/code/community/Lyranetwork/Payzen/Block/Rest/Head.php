<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Block_Rest_Head extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payzen/rest/head.phtml');
    }

    public function getPublicKey()
    {
        $test = $this->_getHelper()->getCommonConfigData('ctx_mode') === 'TEST';
        return $this->getConfigData($test ? 'rest_public_key_test' : 'rest_public_key_prod');
    }

    public function getPlaceholder($name)
    {
        if (! $name) {
            return null;
        }

        $placeholders = unserialize($this->getConfigData('rest_placeholders'));

        if (! is_array($placeholders) || empty($placeholders)) {
            return null;
        }

        if (($placeholder = $placeholders[$name]) && isset($placeholder['placeholder']) && $placeholder['placeholder']) {
            return $placeholder['placeholder'];
        }

        return null;
    }

    public function getTheme()
    {
        return $this->getConfigData('rest_theme');
    }

    public function getReturnUrl()
    {
        return Mage::getUrl('payzen/payment/restReturn', array('_secure' => true));
    }

    public function getStaticUrl()
    {
        return $this->_getHelper()->getCommonConfigData('static_url');
    }

    public function getLanguage()
    {
        $lang = strtolower(substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        return $lang;
    }

    public function getConfigData($name)
    {
        return Mage::getModel('payzen/payment_standard')->getConfigData($name);
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getConfigData('card_info_mode') != 4) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Return payzen data helper.
     *
     * @return Lyranetwork_Payzen_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('payzen');
    }
}
