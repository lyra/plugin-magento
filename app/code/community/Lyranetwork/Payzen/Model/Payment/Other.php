<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Payment_Other extends Lyranetwork_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_other';
    protected $_formBlockType = 'payzen/other';

    protected $_canUseInternal = false;

    protected function _setExtraFields($order)
    {
        $info = $this->getInfoInstance();
        $this->_payzenRequest->set('payment_cards', $info->getCcType());

        $option = unserialize($info->getAdditionalData());

        // Check if capture_delay and validation_mode are overriden.
        if (is_numeric($option['capture_delay'])) {
            $this->_payzenRequest->set('capture_delay', $option['capture_delay']);
        }

        if ($option['validation_mode'] !== '-1') {
            $this->_payzenRequest->set('validation_mode', $option['validation_mode']);
        }

        // Add cart data.
        if ($option['cart_data'] === '1') {
            Mage::helper('payzen/util')->setCartData($order, $this->_payzenRequest, true);
        }
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

        // Load option informations.
        $option = $this->_getMeans($data->getPayzenOtherOption());

        $info->setAdditionalData($option ? serialize($option) : null)
            ->setCcType($data->getPayzenOtherCcType())
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

        if ($quote) {
            $means = $this->getAvailableMeans($quote);
            return ! empty($means);
        }

        return false;
    }

    /**
     * Return available payment means to be displayed on payment method list page.
     *
     * @param  double $amount a given amount
     * @return array[string][array] An array "$code => $option" of availables means
     */
    public function getAvailableMeans($quote = null)
    {
        $configMeans = unserialize($this->getConfigData('payment_means'));

        $amount = $quote ? $quote->getBaseGrandTotal() : null;
        $country = $quote ? $quote->getBillingAddress()->getCountryId() : null;

        $means = array();
        if (is_array($configMeans) && ! empty($configMeans)) {
            foreach ($configMeans as $code => $value) {
                if (empty($value)) {
                    continue;
                }

                if ($country && isset($value['countries']) && ! empty($value['countries'])
                    && ! in_array($country, $value['countries'])) {
                    continue;
                }

                if ((! $amount || ! $value['minimum'] || $amount > $value['minimum'])
                    && (! $amount || ! $value['maximum'] || $amount < $value['maximum'])) {
                    // Means will be available.
                    $means[$code] = $value;
                }
            }
        }

        return $means;
    }

    private function _getMeans($code)
    {
        $options = $this->getAvailableMeans();

        if ($code && $options[$code]) {
            return $options[$code];
        } else {
            return false;
        }
    }
}
