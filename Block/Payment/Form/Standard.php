<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Payment\Form;

class Standard extends Payzen
{
    protected $_template = 'Lyranetwork_Payzen::payment/form/standard.phtml';

    public function getAvailableCcTypes()
    {
        return $this->getMethod()->getAvailableCcTypes();
    }

    public function getCcTypeNetwork($code)
    {
        $cbCards = [
            'CB',
            'VISA',
            'VISA_ELECTRON',
            'MASTERCARD',
            'MAESTRO',
            'E-CARTEBLEUE',
            'VPAY'
        ];

        if ($code == 'AMEX') {
            return 'AMEX';
        } elseif (in_array($code, $cbCards)) {
            return 'CB';
        } else {
            return null;
        }
    }

    public function isLocalCcType()
    {
        return $this->getMethod()->isLocalCcType();
    }

    // Check if the 1-click payment is active for Standard.
    public function isOneClickActive()
    {
        return $this->getMethod()->isOneClickActive();
    }
}
