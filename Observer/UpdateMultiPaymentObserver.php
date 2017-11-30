<?php
/**
 * PayZen V2-Payment Module version 2.1.3 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */
namespace Lyranetwork\Payzen\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class UpdateMultiPaymentObserver implements ObserverInterface
{

    /**
     * Update payment method ID to set installments number if multi payment.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $payment = $observer->getDataObject();

        if ($payment->getMethod() != 'payzen_multi') {
            // not payzen multiple payment, do nothing
            return $this;
        }

        // retreive selected option
        $option = @unserialize($payment->getAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::MULTI_OPTION));
        if (is_array($option) && ! empty($option)) {
            $payment->setMethod('payzen_multi_' . $option['count'] . 'x');
        }

        return $this;
    }
}
