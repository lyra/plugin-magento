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

class Lyra_Payzen_Block_Oneclick_Shipping_Address extends Lyra_Payzen_Block_Oneclick_Shipping_Abstract
{
    private $_uniqId;

    public function getHtmlSelect()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return '';
        }

        $options = array();

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        foreach ($customer->getAddresses() as $address) {
            $options[] = array(
                    'value' => $address->getId(),
                    'label' => $address->format('oneline')
            );
        }

        $select = $this->getLayout()->createBlock('core/html_select')
                ->setName('shipping_address')
                ->setId($this->getUniqId())
                ->setClass('payzen-address-select')
                ->setExtraParams('onchange="payzenUpdateShippingBlock();" style="width: 100%;"')
                ->setValue($this->getAddress()->getCustomerAddressId())
                ->setOptions($options);

        return $select->getHtml();
    }

    public function getUniqId()
    {
        if (!$this->_uniqId) {
            $this->_uniqId = uniqid('payzen_shipping_address_');
        }

        return $this->_uniqId;
    }

    protected function _afterToHtml($html)
    {
        $this->_uniqId = null;

        return parent::_afterToHtml($html);
    }
}
