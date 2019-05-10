<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyra_Payzen_Block_Oneclick_Shipping_Method extends Lyra_Payzen_Block_Oneclick_Shipping_Abstract
{
    protected $_method = null;
    protected $_rates = array();

    public function getShippingRates()
    {
        if (empty($this->_rates)) {
            $this->_rates = $this->getAddress()->getGroupedAllShippingRates();
        }

        return $this->_rates;
    }

    public function getShippingMethod()
    {
        if (null === $this->_method) {
            $this->_method = $this->getAddress()->getShippingMethod();
        }

        return $this->_method;
    }

    public function getCarrierName($carrierCode)
    {
        if ($name = Mage::getStoreConfig('carriers/'.$carrierCode.'/title')) {
            return $name;
        }

        return $carrierCode;
    }

    public function getShippingPrice($price, $flag)
    {
        return $this->getQuote()->getStore()->convertPrice(
            Mage::helper('tax')->getShippingPrice($price, $flag, $this->getAddress()),
            true,
            false
        );
    }

    protected function _afterToHtml($html)
    {
        $this->_method = null;
        $this->_rates = array();

        return parent::_afterToHtml($html);
    }
}
