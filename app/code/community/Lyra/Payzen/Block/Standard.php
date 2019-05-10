<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
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
        )
        ) {
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
