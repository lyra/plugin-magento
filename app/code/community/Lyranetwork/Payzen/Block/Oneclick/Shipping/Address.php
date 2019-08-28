<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Block_Oneclick_Shipping_Address extends Lyranetwork_Payzen_Block_Oneclick_Shipping_Abstract
{
    protected $_uniqId;

    public function getHtmlSelect()
    {
        if (! Mage::getSingleton('customer/session')->isLoggedIn()) {
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
            ->setExtraParams('onchange="payzenUpdateShippingBlock(event);" style="width: 100%;"')
            ->setValue($this->getAddress()->getCustomerAddressId())
            ->setOptions($options);

        return $select->getHtml();
    }

    public function getUniqId()
    {
        if (! $this->_uniqId) {
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
