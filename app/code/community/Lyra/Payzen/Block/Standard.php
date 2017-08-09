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

class Lyra_Payzen_Block_Standard extends Lyra_Payzen_Block_Abstract
{
    protected $_model = 'standard';

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payzen/standard.phtml');
    }

    public function getAvailableCcTypes()
    {
        return $this->_getModel()->getAvailableCcTypes();
    }

    public function getCcTypeNetwork($code)
    {
        if ($code == 'AMEX') {
            return 'AMEX';
        } elseif (in_array($code, array('CB', 'VISA', 'VISA_ELECTRON', 'MASTERCARD', 'MAESTRO', 'E-CARTEBLEUE'))) {
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
