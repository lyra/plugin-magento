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

class Lyra_Payzen_Block_Oney extends Lyra_Payzen_Block_Abstract
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
            // local payment options selection is not allowed
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
