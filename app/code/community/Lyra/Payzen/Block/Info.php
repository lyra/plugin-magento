<?php
/**
 * PayZen V2-Payment Module version 1.8.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
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

class Lyra_Payzen_Block_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payzen/info.phtml');
    }

    public function getTransactionInfoHtml($front = false)
    {
        $collection = Mage::getResourceModel('sales/order_payment_transaction_collection');
        $collection->addPaymentIdFilter($this->getInfo()->getId());
        $collection->load();

        $html = '';

        $frontInfos = array('Transaction Type', 'Amount', 'Transaction ID', 'Payment Mean', '3-DS Authentication');

        foreach ($collection as $item) {
            $html .= '<hr />';

            if (! $front) {
                $html .= Mage::helper('payzen')->__('Sequence Number') . ' : '
                    . substr($item->getTxnId(), strpos($item->getTxnId(), '-') + 1);
                $html .= '<br />';
            }

            $info = $item->getAdditionalInformation('raw_details_info');
            foreach ($info as $key => $value) {
                if (! $value) {
                    continue;
                }

                if ($front && ! in_array($key, $frontInfos)) {
                    continue;
                }

                $html .= Mage::helper('payzen')->__($key) . ' : ' . $value;
                $html .= '<br />';
            }
        }

        return $html;
    }

    public function getResultDescription()
    {
        $allResults = @unserialize($this->getInfo()->getAdditionalInformation(Lyra_Payzen_Helper_Payment::ALL_RESULTS));

        // backward compatibility
        if (! is_array($allResults) || empty($allResults)) {
            $allResults = @unserialize($this->getInfo()->getCcStatusDescription());

            if (! is_array($allResults) || empty($allResults)) {
                // description is stored as litteral string
                return $this->getInfo()->getCcStatusDescription();
            }

            $allResults = array_combine(array('result', 'extra_result', 'auth_result', 'warranty_result'), $allResults);
        }

        // description is stored as serialized array
        $keys = array('result', 'auth_result', 'warranty_result');

        $labels = array();
        foreach ($keys as $key) {
            $label = $this->translate($allResults[$key], $key, true);
            if (! $label) {
                continue;
            }

            if ($key === 'result' && $allResults[$key] == '30') { // append form error if any
                $label .= ' ' . PayzenResponse::extraMessage($allResults['extra_result']);
            }

            $labels[] = $label;
        }

        return implode('<br />', $labels);
    }

    public function translate($code, $type, $appendCode = false)
    {
        $lang = strtolower(substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        return Lyra_Payzen_Model_Api_Response::translate($code, $type, $lang, $appendCode);
    }
}
