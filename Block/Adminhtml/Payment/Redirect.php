<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\Payment;

class Redirect extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Constructor.
     */
    protected function _construct()
    {
        parent::_construct();

        $this->buttonList->remove('reset');
        $this->buttonList->remove('save');
        $this->buttonList->update('back', 'id', 'back_payzen_redirect_button');
        $this->buttonList->update('back', 'onclick', 'setLocation(\'' . $this->getBackUrl() . '\')');
    }

    /**
     * Get URL for back (reset) button.
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('sales/order_create/');
    }
}
