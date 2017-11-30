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
namespace Lyranetwork\Payzen\Model\Method;

class Gift extends Payzen
{

    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_GIFT;

    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Gift::class;

    protected $_canRefund = false;

    protected $_canRefundInvoicePartial = false;

    protected function setExtraFields($order)
    {
        $info = $this->getInfoInstance();

        // override payment_cards
        $this->payzenRequest->set('payment_cards', $info->getCcType());
    }

    /**
     * Assign data to info model instance.
     *
     * @param \Magento\Framework\DataObject $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        // reset payment method specific data
        $this->resetData();

        parent::assignData($data);

        $info = $this->getInfoInstance();

        $payzenData = $this->extractPayzenData($data);
        $info->setCcType($payzenData->getData('payzen_gift_gc_type'));

        return $this;
    }

    /**
     * Return true if the method can be used at this time.
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (! $this->getConfigData('gift_cards')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Get a complete list of available gift cards.
     *
     * @return array[string][string]
     */
    public function getAvailableGcTypes()
    {
        // get selected values from module configuration
        $cards = $this->getConfigData('gift_cards');
        if (empty($cards)) {
            return [];
        }

        $cards = explode(',', $cards);

        $availCards = [];
        foreach ($this->getSupportedGcTypes() as $code => $label) {
            if (in_array($code, $cards)) {
                $availCards[$code] = $label;
            }
        }
        return $availCards;
    }

    /**
     * Get a complete list of supported gift cards.
     *
     * @return array[string][string]
     */
    public function getSupportedGcTypes()
    {
        $options = $this->_getHelper()->getConfigArray('gift_cards'); // the default gift cards

        $addedCards = $this->dataHelper->unserialize($this->getConfigData('added_gift_cards')); // the user-added gift cards
        if (is_array($addedCards) && ! empty($addedCards)) {
            foreach ($addedCards as $code => $value) {
                if (empty($value)) {
                    continue;
                }

                $options[$value['code']] = $value['name'];
            }
        }

        return $options;
    }
}
