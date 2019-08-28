<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Block_Oney extends Lyranetwork_Payzen_Block_Abstract
{
    protected $_model = 'oney';

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payzen/oney.phtml');
    }

    public function getPaymentOptions()
    {
        if ($this->_getModel()->getConfigData('enable_payment_options') != 1) {
            // Local payment options selection is not allowed.
            return false;
        }

        $amount = $this->getMethod()->getInfoInstance()->getQuote()->getBaseGrandTotal();
        return $this->_getModel()->getPaymentOptions($amount);
    }

    public function getHtmlReview(array $option, $first)
    {
        $quote = $this->getMethod()->getInfoInstance()->getQuote();
        $amount = $quote->getGrandTotal();

        $block = $this->getLayout()->createBlock('payzen/oney_review')
            ->setOption($option)
            ->setAmount($amount)
            ->setFirst($first);

        return $block->toHtml();
    }
}
