<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Payment_Ideal extends Lyranetwork_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_ideal';
    protected $_formBlockType = 'payzen/ideal';

    protected $_canUseInternal = false;

    protected  function _setExtraFields($order)
    {
        // Override with iDEAL payment card.
        $this->_payzenRequest->set('payment_cards', 'IDEAL');
    }

    /**
     * Assign data to info model instance
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
}
