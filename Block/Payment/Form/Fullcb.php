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

class Fullcb extends Payzen
{

    protected $_template = 'Lyranetwork_Payzen::payment/form/fullcb.phtml';

    public function getAvailableOptions()
    {
        if (! $this->getConfigData('enable_payment_options')) {
            // Local payment options selection is not allowed.
            return [];
        }

        $amount = $this->getMethod()
            ->getInfoInstance()
            ->getQuote()
            ->getBaseGrandTotal();
        return $this->getMethod()->getAvailableOptions($amount);
    }
}
