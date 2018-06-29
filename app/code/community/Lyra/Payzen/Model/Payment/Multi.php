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

class Lyra_Payzen_Model_Payment_Multi extends Lyra_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_multi';
    protected $_formBlockType = 'payzen/multi';

    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;

    protected function _setExtraFields($order)
    {
        if ($this->_getHelper()->isAdmin()) {
            // set payment_src to MOTO for backend payments
            $this->_payzenRequest->set('payment_src', 'MOTO');
        }

        $info = $this->getInfoInstance();

        if (! $this->_getHelper()->isAdmin() && ($this->getConfigData('card_info_mode') == 2)) {
            $this->_payzenRequest->set('payment_cards', $info->getCcType());
        } else {
            // payment_cards is given as csv by magento
            $paymentCards = explode(',', $this->getConfigData('payment_cards'));
            $paymentCards = in_array('', $paymentCards) ? '' : implode(';', $paymentCards);

            $this->_payzenRequest->set('payment_cards', $paymentCards);
        }

        // set mutiple payment option
        $option = unserialize($info->getAdditionalData());

        $amount = $this->_payzenRequest->get('amount');
        $first = ($option['first'] != '') ? round(($option['first'] / 100) * $amount) : null;
        $this->_payzenRequest->setMultiPayment($amount, $first, $option['count'], $option['period']);
        $this->_payzenRequest->set('contracts', (isset($option['contract']) && $option['contract']) ? 'CB=' . $option['contract'] : null);

        $this->_getHelper()->log('Multiple payment configuration is ' . $this->_payzenRequest->get('payment_config'));
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (! ($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        // load option informations
        $option = $this->_getOption($data->getPayzenMultiOption());

        $info->setAdditionalData($option ? serialize($option) : null)
                ->setCcType($data->getPayzenMultiCcType())
                ->setCcLast4(null)
                ->setCcNumber(null)
                ->setCcCid(null)
                ->setCcExpMonth(null)
                ->setCcExpYear(null);

        return $this;
    }

    /**
     * Return true if the method can be used at this time
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (! parent::isAvailable($quote)) {
            return false;
        }

        $amount = $quote ? $quote->getBaseGrandTotal() : null;
        if ($amount) {
            $options = $this->getAvailableOptions($amount);
            return ! empty($options);
        }

        return false;
    }

    /**
     * Return available payment options to be displayed on payment method list page.
     *
     * @param double $amount a given amount
     * @return array[string][array] An array "$code => $option" of availables options
     */
    public function getAvailableOptions($amount = null)
    {
        $configOptions = unserialize($this->getConfigData('payment_options'));

        $options = array();
        if (is_array($configOptions) && ! empty($configOptions)) {
            foreach ($configOptions as $code => $value) {
                if (empty($value)) {
                    continue;
                }

                if ((! $amount || ! $value['minimum'] || $amount > $value['minimum'])
                    && (! $amount || ! $value['maximum'] || $amount < $value['maximum'])) {
                    // option will be available
                    $options[$code] = $value;
                }
            }
        }

        return $options;
    }

    protected function _getOption($code)
    {
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $amount = $info->getOrder()->getBaseGrandTotal();
        } else {
            $amount = $info->getQuote()->getBaseGrandTotal();
        }
        $options = $this->getAvailableOptions($amount);

        if ($code && $options[$code]) {
            return $options[$code];
        } else {
            return false;
        }
    }

    /**
     * Return available card types
     *
     * @return string
     */
    public function getAvailableCcTypes()
    {
        // all cards
        $allCards = Mage::getModel('payzen/source_multi_paymentCards')->getMultiCards();

        // selected cards from module configuration
        $cards = $this->getConfigData('payment_cards');
        $cards = ! empty($cards) ? explode(',', $cards) : array();

        $availCards = array();

        if (empty($cards)) {
            $availCards = $allCards;
        } else {
            // get card labels
            foreach ($allCards as $code => $name) {
                if (in_array($code, $cards)) {
                    $availCards[$code] = $name;
                }
            }
        }

        return $availCards;
    }
}
