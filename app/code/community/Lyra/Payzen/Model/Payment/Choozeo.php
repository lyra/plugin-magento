<?php
/**
 * PayZen V2-Payment Module version 1.9.1 for Magento 1.4-1.9. Support contact : support@payzen.eu.
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

class Lyra_Payzen_Model_Payment_Choozeo extends Lyra_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_choozeo';
    protected $_formBlockType = 'payzen/choozeo';

    protected $_canUseInternal = false;

    protected $_currencies = array('EUR');

    protected  function _setExtraFields($order)
    {
        // override some form data
        $this->_payzenRequest->set('validation_mode', '0');
        $this->_payzenRequest->set('cust_status', 'PRIVATE');

        // send phone number as cell phone
        $this->_payzenRequest->set('cust_cell_phone', $order->getBillingAddress()->getTelephone());

        // override with selected Choozeo payment card
        $info = $this->getInfoInstance();
        $this->_payzenRequest->set('payment_cards', $info->getCcType());
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
        $info->setCcType($data->getPayzenChoozeoCcType())
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

        /** @var array[string][string] $options */
        $options = array(
            'EPNF_3X' => 'Choozeo 3X CB',
            'EPNF_4X' => 'Choozeo 4X CB'
        );

        $availOptions = array();
        if (is_array($configOptions) && ! empty($configOptions)) {
            foreach ($configOptions as $code => $value) {
                if (empty($value)) {
                    continue;
                }

                if ((! $amount || ! $value['amount_min'] || $amount > $value['amount_min'])
                    && (! $amount || ! $value['amount_max'] || $amount < $value['amount_max'])) {
                    $value['label'] = $options[$value['code']];

                    // option will be available
                    $availOptions[$code] = $value;
                }
            }
        }

        return $availOptions;
    }
}
