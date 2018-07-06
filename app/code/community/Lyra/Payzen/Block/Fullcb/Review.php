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

class Lyra_Payzen_Block_Fullcb_Review extends Mage_Core_Block_Template
{
    const FULLCB_THREE_TIMES_MAX_FEES = 9;
    const FULLCB_FOUR_TIMES_MAX_FEES = 12;

    protected $_option;
    protected $_amount;
    protected $_first;

    /**
     * Set template for fullcb review.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payzen/fullcb/review.phtml');
    }

    /**
     * Set Fullcb option to review.
     *
     * @param string $url
     * @return Lyra_Payzen_Block_Fullcb_Review
     */
    public function setOption($option)
    {
        $this->_option = $option;
        return $this;
    }

    /**
     * Set order amount.
     *
     * @param float $amount
     * @return Lyra_Payzen_Block_Fullcb_Review
     */
    public function setAmount($amount)
    {
        $this->_amount = $amount;
        return $this;
    }

    /**
     * Set first option flag.
     *
     * @param bool $first
     * @return Lyra_Payzen_Block_Oney_Review
     */
    public function setFirst($first)
    {
        $this->_first = $first;
        return $this;
    }

    public function getFirst()
    {
        return $this->_first;
    }

    public function getOptionDetails()
    {
        $details = new Varien_Object();

        if (is_array($this->_option) && ! empty($this->_option)) {
            $amount = $this->_amount;
            $count = (int) $this->_option['count'];
            $rate = (float) $this->_option['rate'];

            $maxFees = (int) $this->_option['cap'];
            switch ($count) {
                case 3:
                    $maxFees = self::FULLCB_THREE_TIMES_MAX_FEES;
                    break;
                case 4:
                    $maxFees = self::FULLCB_FOUR_TIMES_MAX_FEES;
                    break;
                default:
                    $maxFees = null;
                    break;
            }

            $payment = round($amount / $count, 2);

            $fees = round($amount * $rate / 100, 2);
            if ($maxFees) {
                $fees = min($fees, $maxFees);
            }

            $first = $amount - ($payment * ($count - 1)) + $fees;

            $details->setOptionCode($this->_option['code']);
            $details->setOrderTotal($this->currency($amount));
            $details->setFirstPayment($this->currency($first));
            $details->setCount($count - 1); // real number of payments concerned by funding
            $details->setMonthlyPayment($this->currency($payment));
            $details->setFundingTotal($this->currency($amount + $fees));
            $details->setFundingFees($this->currency($fees));
        }

        return $details;
    }

    public function currency($amount)
    {
        return Mage::helper('core')->currency($amount, true, true);
    }
}
