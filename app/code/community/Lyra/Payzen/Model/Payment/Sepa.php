<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyra_Payzen_Model_Payment_Sepa extends Lyra_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_sepa';
    protected $_formBlockType = 'payzen/sepa';

    protected $_canUseInternal = false;

    protected $_currencies = array('EUR');

    protected  function _setExtraFields($order)
    {
        // Override with SEPA payment card.
        $this->_payzenRequest->set('payment_cards', 'SDD');
        $this->_payzenRequest->set('page_action', $this->getConfigData('mandate_mode'));
    }

    /**
     * Assign data to info model instance.
     *
     * @param  mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        $info = $this->getInfoInstance();

        // Init all payment data.
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
     * To check billing country is allowed for SEPA payment method.
     *
     * @return bool
     */
    public function canUseForCountry($country)
    {
        $availableCountries = Mage::getModel('payzen/source_sepa_availableCountries')->getCountryCodes();

        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));
        }

        return in_array($country, $availableCountries);
    }
}
