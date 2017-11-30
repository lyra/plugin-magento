<?php
/**
 * PayZen V2-Payment Module version 1.8.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */

class Lyra_Payzen_Model_Payment_Giropay extends Lyra_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_giropay';
    protected $_formBlockType = 'payzen/giropay';

    protected $_canUseInternal = false;

    protected  function _setExtraFields($order)
    {
        // override with Giropay payment card
        $this->_payzenRequest->set('payment_cards', 'GIROPAY');
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
}
