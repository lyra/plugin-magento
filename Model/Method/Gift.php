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

class Gift extends Payzen
{
    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_GIFT;
    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Gift::class;

    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;

    protected function setExtraFields($order)
    {
        $info = $this->getInfoInstance();

        // Override payment_cards.
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
        parent::assignData($data);

        $info = $this->getInfoInstance();

        $payzenData = $this->extractPaymentData($data);
        $info->setCcType($payzenData->getData('payzen_gift_cc_type'));

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
    public function getAvailableCcTypes()
    {
        // Get selected values from module configuration.
        $cards = $this->getConfigData('gift_cards');
        if (empty($cards)) {
            return [];
        }

        $cards = explode(',', $cards);

        $availCards = [];
        foreach ($this->getSupportedCcTypes() as $code => $label) {
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
    public function getSupportedCcTypes()
    {
        // The default gift cards.
        $options = [
            'ILLICADO'      => 'Carte Illicado',
            'ILLICADO_SB'   => 'Carte Illicado (Sandbox)',
            'TRUFFAUT_CDX'  => 'Carte Cadeau Truffaut',
            'ALINEA_CDX'    => 'Carte Cadeau Alinéa',
            'ALINEA_CDX_SB' => 'Carte Cadeau Alinéa (Sandbox)'
        ];

        $addedCards = $this->dataHelper->unserialize($this->getConfigData('added_gift_cards')); // The user-added gift cards.
        if (is_array($addedCards) && ! empty($addedCards)) {
            foreach ($addedCards as $value) {
                if (empty($value)) {
                    continue;
                }

                $options[$value['code']] = $value['name'];
            }
        }

        return $options;
    }
}
