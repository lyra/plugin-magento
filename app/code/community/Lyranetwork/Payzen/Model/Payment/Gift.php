<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Payment_Gift extends Lyranetwork_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_gift';
    protected $_formBlockType = 'payzen/gift';

    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;

    protected function _setExtraFields($order)
    {
        if ($this->_getHelper()->isAdmin()) {
            // Set payment_src to MOTO for backend payments.
            $this->_payzenRequest->set('payment_src', 'MOTO');
        }

        $info = $this->getInfoInstance();

        // Override payment_cards.
        $this->_payzenRequest->set('payment_cards', $info->getCcType());
    }

    /**
     * Assign data to info model instance
     *
     * @param  mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (! ($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();
        $info->setCcType($data->getPayzenGiftGcType())
            ->setCcLast4(null)
            ->setCcNumber(null)
            ->setCcCid(null)
            ->setCcExpMonth(null)
            ->setCcExpYear(null)
            ->setAdditionalData(null);

        return $this;
    }

    /**
     * Return true if the method can be used at this time
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (! $this->getConfigData('gift_cards')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Get a complete list of available gift cards.
     *
     * @return array[string][string]
     */
    public function getAvailableGcTypes()
    {
        // Get selected values from module configuration.
        $cards = $this->getConfigData('gift_cards');
        if (empty($cards)) {
            return array();
        }

        $cards = explode(',', $cards);

        $availCards = array();
        foreach ($this->getSupportedGcTypes() as $code => $label) {
            if (in_array($code, $cards)) {
                $availCards[$code] = $label;
            }
        }

        return $availCards;
    }

    /**
     * Get a complete list of supported gift cards.
     *
     * @return array[string][string]
     */
    public function getSupportedGcTypes()
    {
        $options = $this->_getHelper()->getConfigArray('gift_cards'); // The default gift cards.

        $addedCards = unserialize($this->getConfigData('added_gift_cards')); // The user-added gift cards.
        if (is_array($addedCards) && ! empty($addedCards)) {
            foreach ($addedCards as $value) {
                if (empty($value)) {
                    continue;
                }

                $options[$value['code']] = $value['name'];
            }
        }

        return $options;
    }
}
