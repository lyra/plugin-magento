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

class Wallet extends \Magento\Vault\Block\Customer\CreditCards
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    protected $method;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Vault\Model\CustomerTokenManagement $customerTokenManagement,
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Vault\Model\CustomerTokenManagement $customerTokenManagement,
        \Magento\Customer\Model\Session $customerSession,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $customerTokenManagement);

        $this->method = $this->dataHelper->getMethodInstance(\Lyranetwork\Payzen\Helper\Data::METHOD_STANDARD);
    }

    /**
     * Get Form data by using ops payment api.
     *
     * @return array
     */
    public function getStoredPaymentMeans($identifier = null)
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

        if ($identifier) {
            $identifierToUnset = ($identifier == 'payzen_identifier') ? 'payzen_sepa_identifier' : 'payzen_identifier';
            unset($aliasIds[$identifierToUnset]);
        }

        foreach ($aliasIds as $aliasId => $maskedId) {
            // Check if there is a saved alias.
            if (! $customer->getCustomAttribute($aliasId)) {
                continue;
            }

            $card = [];
            $card['alias'] = $aliasId;
            $card['pm'] = $maskedId;

            $maskedPan = $customer->getCustomAttribute($maskedId)->getValue();
            if (($maskedPan != null) && ($pos = strpos($maskedPan, '|'))) {
                $card['brand'] = substr($maskedPan, 0, $pos);
                $number = substr($maskedPan, $pos + 1);

                if (($number != null) && ($pos = strpos($number, '-'))) {
                    $card['expiry'] = substr($number, $pos + 2);
                    $number = substr($number, 0, $pos);
                }

                $card['number'] = $number;
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

    public function getAccountToken()
    {
        if (! $this->useCustomerWallet()) {
            return null;
        }

        return $this->method->getAccountToken();
    }

    public function getLanguage()
    {
        return $this->method->getPaymentLanguage();
    }

    public function hideWalletElements()
    {
        return $this->dataHelper->onVaultTab() && ! $this->method->isRestMode();
    }

    public function hasIdentifiers()
    {
        return ! empty($this->getStoredPaymentMeans());
    }

    private function useCustomerWallet()
    {
        $customer = $this->method->getCurrentCustomer();
        if (! $customer || ! $this->method->isOneClickActive()) {
            return false;
        }

        return true;
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