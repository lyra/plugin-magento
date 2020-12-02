<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_CustomerController extends Mage_Core_Controller_Front_Action
{
    /**
     * Redirect customer to the payment gateway.
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');

        $this->getLayout()->getBlock('head')->setTitle($this->__('My Payment Means'));

        if ($block = $this->getLayout()->getBlock('customer.account.link.back')) {
            $block->setRefererUrl($this->_getRefererUrl());
        }

        $this->renderLayout();
    }

    public function cancelAction()
    {
        // Clear all messages from session.
        Mage::getSingleton('core/session')->getMessages(true);

        Mage::helper('payzen')->log('Start =================================================');
        $attribute = $this->getRequest()->getPost('alias', false);
        $maskedAttribute = $this->getRequest()->getPost('pm', false);

        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();

        if ($customer && $session->isLoggedIn() && $attribute && $maskedAttribute) {
            if (Mage::helper('payzen/payment')->deleteIdentifier($attribute, $maskedAttribute)) {
                Mage::getSingleton('core/session')->addSuccess(__('The stored means of payment was successfully deleted.'));
            } else {
                Mage::getSingleton('core/session')->addError(__('The stored means of payment could not be deleted.'));
            }
        }

        Mage::helper('payzen')->log('End =================================================');
    }
}
