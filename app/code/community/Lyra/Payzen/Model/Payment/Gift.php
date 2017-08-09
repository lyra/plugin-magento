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

class Lyra_Payzen_Model_Payment_Gift extends Lyra_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_gift';
    protected $_formBlockType = 'payzen/gift';

    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;

    protected function _setExtraFields($order)
    {
        if ($this->_getHelper()->isAdmin()) {
            // set payment_src to MOTO for backend payments
            $this->_payzenRequest->set('payment_src', 'MOTO');
        }

        $info = $this->getInfoInstance();

        // override payment_cards
        $this->_payzenRequest->set('payment_cards', $info->getCcType());
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
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
        if (!$this->getConfigData('gift_cards')) {
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
        // get selected values from module configuration
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
        $options = $this->_getHelper()->getConfigArray('gift_cards'); // the default gift cards

        $addedCards = unserialize($this->getConfigData('added_gift_cards')); // the user-added gift cards
        if (is_array($addedCards) && !empty($addedCards)) {
            foreach ($addedCards as $code => $value) {
                if (empty($value)) {
                    continue;
                }

                $options[$value['code']] = $value['name'];
            }
        }

        return $options;
    }
}
