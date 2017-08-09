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

class Lyra_Payzen_Model_Payment_Cof3xcb extends Lyra_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_cof3xcb';
    protected $_formBlockType = 'payzen/cof3xcb';

    protected  function _setExtraFields($order)
    {
        $title = $order->getBillingAddress()->getPrefix();
        if (strlen($title) > 5) {
            $title = substr($title, 0, 5); // take the 5 first chars
        }
        $this->_payzenRequest->set('cust_title', $title);

        $testMode = $this->_payzenRequest->get('ctx_mode') == 'TEST';

        // override payment_cards
        $this->_payzenRequest->set('payment_cards', $testMode ? 'COF3XCB_SB' : 'COF3XCB');
    }

    /**
     * Assign data to info model instance
     *
     * @param mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        $info = $this->getInfoInstance();

        // init all payment data
        $info->setCcType(null)
                ->setCcLast4(null)
                ->setCcNumber(null)
                ->setCcCid(null)
                ->setCcExpMonth(null)
                ->setCcExpYear(null)
                ->setAdditionalData(null);

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @param   Mage_Payment_Model_Info $info
     * @return  Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        /*
         * calling parent validate function
        */
        parent::validate();

        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $address = $info->getOrder()->getBillingAddress();
            $email = $info->getOrder()->getCustomerEmail();
        } else {
            $address = $info->getQuote()->getBillingAddress();
            $email = $info->getQuote()->getCustomerEmail();
        }

        $errors = array();

        if (!$email) {
            $errors[] = $this->_getHelper()->__('Email');
        }
        if (!$address->getFirstname()){
            $errors[] = $this->_getHelper()->__('First Name');
        }
        if (!$address->getLastname()) {
            $errors[] = $this->_getHelper()->__('Last Name');
        }
        if (!$address->getPrefix()){
            $errors[] = $this->_getHelper()->__('Civility');
        }
        if (!$address->getStreet(1)) {
            $errors[] = $this->_getHelper()->__('Address');
        }
        if (!$address->getPostcode()) {
            $errors[] = $this->_getHelper()->__('Postcode');
        }
        if (!$address->getCity()) {
            $errors[] = $this->_getHelper()->__('City');
        }
        if (!$address->getCountryId()) {
            $errors[] = $this->_getHelper()->__('Country');
        }
        if (!$address->getTelephone()) {
            $errors[] = $this->_getHelper()->__('Telephone');
        }

        if (count($errors) == 1) {
            Mage::throwException($this->_getHelper()->__('The field %s is required for this payment mean.', implode(', ', $errors)));
        } elseif (count($errors) > 1) {
            Mage::throwException($this->_getHelper()->__('The fields %s are required for this payment mean.', implode(', ', $errors)));
        }

        return $this;
    }
}
