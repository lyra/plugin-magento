<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\Method;

class Paypal extends Payzen
{

    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_PAYPAL;
    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Paypal::class;

    protected $_canUseInternal = false;

    protected $needsCartData = true;

    protected function setExtraFields($order)
    {
        $testMode = $this->payzenRequest->get('ctx_mode') == 'TEST';

        // Override with PayPal payment cards.
        $this->payzenRequest->set('payment_cards', $testMode ? 'PAYPAL_SB' : 'PAYPAL');
    }
}
