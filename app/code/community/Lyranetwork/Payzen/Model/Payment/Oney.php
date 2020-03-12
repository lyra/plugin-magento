<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Payment_Oney extends Lyranetwork_Payzen_Model_Payment_Oney3x4x
{
    protected $_code = 'payzen_oney';
    protected $_formBlockType = 'payzen/oney';

    protected  function _setExtraFields($order)
    {
        $testMode = $this->_payzenRequest->get('ctx_mode') === 'TEST';

        // Override with FacilyPay Oney payment cards.
        $this->_payzenRequest->set('payment_cards', $testMode ? 'ONEY_SANDBOX' : 'ONEY');

        // Set choosen option if any.
        $info = $this->getInfoInstance();
        if ($info->getAdditionalData() && ($option = @unserialize($info->getAdditionalData()))) {
            $this->_payzenRequest->set('payment_option_code', $option['code']);
        }
    }

    protected function _isNewOneyApi()
    {
        return false;
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

        $option = $this->_getOption($data->getPayzenOneyOption());

        // Init all payment data.
        $info->setAdditionalData($option ? serialize($option) : null)
            ->setCcType(null)
            ->setCcLast4(null)
            ->setCcNumber(null)
            ->setCcCid(null)
            ->setCcExpMonth(null)
            ->setCcExpYear(null);

        return $this;
    }

    protected function _canUseForOptions($quote = null)
    {
        return true;
    }
}
