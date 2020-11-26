<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Customer;

class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get Form data by using ops payment api.
     *
     * @return array
     */
    public function getStoredPaymentMeans()
    {
        $means = [];

        // Customer not logged in.
        $customer = $this->dataHelper->getCurrentCustomer($this->customerSession);
        if (! $customer) {
            return $means;
        }

        $aliasIds = [
            'payzen_identifier' => 'payzen_masked_pan',
            'payzen_sepa_identifier' => 'payzen_sepa_iban_bic'
        ];

        foreach ($aliasIds as $aliasId => $maskedId) {
            // Check if there is a saved alias.
            if (! $customer->getCustomAttribute($aliasId)) {
                continue;
            }

            $card = [];
            $card['alias'] = $aliasId;
            $card['pm'] = $maskedId;

            $maskedPan = $customer->getCustomAttribute($maskedId)->getValue();
            $pos = strpos($maskedPan, '|');

            if ($pos !== false) {
                $card['brand'] = substr($maskedPan, 0, $pos);
                $card['number'] = substr($maskedPan, $pos + 1);
            } else {
                $card['brand'] = '';
                $card['number'] = $maskedPan;
            }

            $means[] = $card;
        }

        return $means;
    }

    public function getCustomerId()
    {
        $customer = $this->customerSession->getCustomer();
        return $customer->getId();
    }

    public function getCcTypeImageSrc($card)
    {
        return $this->dataHelper->getCcTypeImageSrc($card);
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (! $this->dataHelper->isOneClickActive()) {
            return '';
        }

        return parent::_toHtml();
    }
}
