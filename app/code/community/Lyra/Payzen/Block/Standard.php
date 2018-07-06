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

class Lyra_Payzen_Block_Standard extends Lyra_Payzen_Block_Abstract
{
    protected $_model = 'standard';

    protected $_manualCcTypes = array(
        'AMEX', 'AURORE-MULTI', 'BUT', 'CASINO', 'CB', 'CDGP', 'CDISCOUNT', 'COFINOGA', 'CONFORAMA', 'CORA_BLANCHE',
        'CORA_PREM', 'CORA_VISA', 'DINERS', 'DISCOVER', 'E-CARTEBLEUE', 'EDENRED_EC', 'EDENRED_TR', 'JCB', 'LECLERC',
        'MASTERCARD', 'PAYBOX', 'PAYDIREKT', 'PRV_BDP', 'PRV_BDT', 'PRV_OPT', 'PRV_SOC', 'SDD', 'SOFICARTE', 'SYGMA',
        'VISA', 'VISA_ELECTRON', 'VPAY'
    );

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payzen/standard.phtml');
    }

    public function getAvailableCcTypes()
    {
        return $this->_getModel()->getAvailableCcTypes();
    }

    public function getAvailableManualCcTypes()
    {
        $allCards = $this->getAvailableCcTypes();
        $cards = array();

        foreach ($this->_manualCcTypes as $cc) {
            if (! key_exists($cc, $allCards)) {
                continue;
            }

            $cards[$cc] = $allCards[$cc];
        }

        return $cards;
    }

    public function getCcTypeNetwork($code)
    {
        if ($code == 'AMEX') {
            return 'AMEX';
        } elseif (in_array(
            $code, array('CB', 'VISA', 'VISA_ELECTRON', 'MASTERCARD', 'MAESTRO', 'E-CARTEBLEUE', 'VPAY')
        )) {
            return 'CB';
        } else {
            return null;
        }
    }

    public function isLocalCcInfo()
    {
        return $this->_getModel()->isLocalCcInfo();
    }

    public function isLocalCcType()
    {
        return $this->_getModel()->isLocalCcType();
    }
}
