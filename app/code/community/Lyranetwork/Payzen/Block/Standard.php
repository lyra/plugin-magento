<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Block_Standard extends Lyranetwork_Payzen_Block_Abstract
{
    protected $_model = 'standard';

    protected $_manualCcTypes = array(
        'AMEX', 'AURORE-MULTI', 'BUT', 'CASINO', 'CB', 'CDGP', 'CDISCOUNT', 'COFINOGA', 'CONFORAMA', 'CORA_BLANCHE',
        'CORA_PREM', 'CORA_VISA', 'DINERS', 'DISCOVER', 'E-CARTEBLEUE', 'EDENRED_EC', 'EDENRED_TR', 'JCB', 'LECLERC',
        'MASTERCARD', 'PAYBOX', 'PAYDIREKT', 'PRV_BDP', 'PRV_BDT', 'PRV_OPT', 'PRV_SOC', 'SDD', 'SOFICARTE', 'SYGMA',
        'VISA', 'VISA_ELECTRON', 'VPAY'
    );

    /**
     * @var string|boolean
     */
    private $_formToken = false;

    /**
     * @var string|boolean
     */
    private $_identifierFormToken = false;

    protected function _construct()
    {
        parent::_construct();

        if (($this->getConfigData('card_info_mode') == '4') && $this->getFormToken()) {
            $this->setTemplate('payzen/standard_rest.phtml');
        } else {
            $this->setTemplate('payzen/standard.phtml');
        }
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
        if ($code === 'AMEX') {
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

    public function isLocalCcType()
    {
        return $this->_getModel()->isLocalCcType();
    }

    public function getFormToken()
    {
        if (! $this->_formToken) {
            $this->_formToken = $this->_getModel()->getFormToken(false, ! $this->getRequest()->isXmlHttpRequest());
        }

        return $this->_formToken;
    }

    public function getIdentifierFormToken()
    {
        if (! $this->_identifierFormToken) {
            $this->_identifierFormToken = $this->_getModel()->getFormToken(true, ! $this->getRequest()->isXmlHttpRequest());
        }

        return $this->_identifierFormToken;
    }

    public function getLanguage()
    {
        $lang = strtolower(substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        return $lang;
    }
}
