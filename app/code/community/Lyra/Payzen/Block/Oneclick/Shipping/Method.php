<?php
/**
 * PayZen V2-Payment Module version 1.9.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
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
