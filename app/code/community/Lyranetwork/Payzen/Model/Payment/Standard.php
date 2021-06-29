<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Payment_Standard extends Lyranetwork_Payzen_Model_Payment_Abstract
{
    protected $_code = 'payzen_standard';
    protected $_formBlockType = 'payzen/standard';

    protected function _setExtraFields($order)
    {
        $info = $this->getInfoInstance();

        if (! $this->_getHelper()->isAdmin() && $this->isLocalCcType()) {
            // Set payment_cards.
            $this->_payzenRequest->set('payment_cards', $info->getCcType());

            if ($info->getCcType() === 'BANCONTACT') {
                // May not disable 3DS for Bancontact Mistercash.
                $this->_payzenRequest->set('threeds_mpi', null);
            }
        } else {
            // Payment_cards is given as csv by Magento.
            $paymentCards = explode(',', $this->getConfigData('payment_cards'));
            $paymentCards = in_array('', $paymentCards) ? '' : implode(';', $paymentCards);

            if ($paymentCards && $this->getConfigData('use_oney_in_standard')) {
                $testMode = $this->_payzenRequest->get('ctx_mode') === 'TEST';

                // Add FacilyPay Oney payment cards.
                $paymentCards .= ';' . ($testMode ? 'ONEY_SANDBOX' : 'ONEY');
            }

            $this->_payzenRequest->set('payment_cards', $paymentCards);
        }

        if ($this->_getHelper()->isAdmin()) {
            // Set payment_src to MOTO for backend payments.
            $this->_payzenRequest->set('payment_src', 'MOTO');
            return;
        }

        $session = Mage::getSingleton('payzen/session');
        if ($this->isIframeMode() && ! $session->getPayzenOneclickPayment() /* No iframe for 1-Click. */) {
            // Iframe enabled and this is not 1-Click.
            $this->_payzenRequest->set('action_mode', 'IFRAME');

            // Hide logos below payment fields.
            $this->_payzenRequest->set('theme_config', $this->_payzenRequest->get('theme_config') . '3DS_LOGOS=false;');

            // Enable automatic redirection.
            $this->_payzenRequest->set('redirect_enabled', '1');
            $this->_payzenRequest->set('redirect_success_timeout', '0');
            $this->_payzenRequest->set('redirect_error_timeout', '0');

            $returnUrl = $this->_payzenRequest->get('url_return');
            $this->_payzenRequest->set('url_return', $returnUrl . '?iframe=true');
        }

        if ($this->getConfigData('one_click_active') && $order->getCustomerId()) {
            // Payment by token enabled and customer logged-in.
            $customer = Mage::getModel('customer/customer');
            $customer->load($order->getCustomerId());

            if ($customer->getPayzenIdentifier() && Mage::getSingleton('checkout/session')->getValidAlias()) {
                // Customer has an identifier.
                $this->_getHelper()->log("Customer {$customer->getEmail()} has an identifier. Use it for payment of order #{$order->getIncrementId()}.");
                $this->_payzenRequest->set('identifier', $customer->getPayzenIdentifier());

                if (! $info->getAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::IDENTIFIER)) {
                    // Customer choose to not use token.
                    $this->_payzenRequest->set('page_action', 'REGISTER_UPDATE_PAY');
                }
            } else {
                // Card data entry on payment page, let's ask customer for data registration.
                $this->_getHelper()->log("Customer {$customer->getEmail()} will be asked for card data registration on payment page for order #{$order->getIncrementId()}.");
                $this->_payzenRequest->set('page_action', 'ASK_REGISTER_PAY');
            }
        }
    }

    protected function _proposeOney()
    {
        $info = $this->getInfoInstance();

        return (! $info->getCcType() && $this->getConfigData('use_oney_in_standard'))
            || in_array($info->getCcType(), array('ONEY_SANDBOX', 'ONEY'));
    }

    private function _getFormTokenData($quote, $useIdentifier = false)
    {
        $amount = $quote->getGrandTotal();
        $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($quote->getOrderCurrencyCode());
        if ($currency == null) {
            // If currency is not supported, use base currency
            $currency = Lyranetwork_Payzen_Model_Api_Api::findCurrencyByAlphaCode($quote->getBaseCurrencyCode());

            // ... and order total in base currency.
            $amount = $quote->getBaseGrandTotal();
        }

        $language = strtolower(substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        if (! Lyranetwork_Payzen_Model_Api_Api::isSupportedLanguage($language)) {
            $language = $this->_getHelper()->getCommonConfigData('language');
        }

        // Check if capture_delay and validation_mode are overriden in standard submodule.
        if (is_numeric($this->getConfigData('capture_delay'))) {
            $captureDelay = $this->getConfigData('capture_delay');
        } else {
            $captureDelay = $this->_getHelper()->getCommonConfigData('capture_delay');
        }

        if ($this->getConfigData('validation_mode') !== '-1') {
            $validationMode = $this->getConfigData('validation_mode');
        } else {
            $validationMode = $this->_getHelper()->getCommonConfigData('validation_mode');
        }

        // Activate 3DS ?
        $strongAuth = 'AUTO';
        $threedsMinAmount = $this->_getHelper()->getCommonConfigData('threeds_min_amount');
        if ($threedsMinAmount && $quote->getTotalDue() < $threedsMinAmount) {
            $strongAuth = 'DISABLED';
        }

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        $methodCode = Mage::helper('payzen/util')->toPayzenCarrier($shippingAddress->getShippingMethod());

        $contrib = $this->_getHelper()->getCommonConfigData('cms_identifier') . '_' .
            $this->_getHelper()->getCommonConfigData('plugin_version') . '/';

        if (! $quote->getReservedOrderId()) {
            // Reserve order ID.
            $quote->reserveOrderId()->save();
        }

        $data = array(
            'orderId' => $quote->getReservedOrderId(),
            'customer' => array(
                'email' => $quote->getCustomerEmail(),
                'reference' => $quote->getCustomer()->getId(),
                'billingDetails' => array(
                    'language' => strtoupper($language),
                    'title' => $billingAddress->getPrefix() ? $billingAddress->getPrefix() : null,
                    'firstName' => $billingAddress->getFirstname(),
                    'lastName' => $billingAddress->getLastname(),
                    'address' => $billingAddress->getStreet(1) . ' ' . $billingAddress->getStreet(2),
                    'zipCode' => $billingAddress->getPostcode(),
                    'city' => $billingAddress->getCity(),
                    'state' => !is_numeric($billingAddress->getRegionCode()) ?
                        $billingAddress->getRegionCode() : $billingAddress->getRegion(),
                    'phoneNumber' => $billingAddress->getTelephone(),
                    'country' => $billingAddress->getCountryId()
                ),
                'shippingDetails' => array(
                    'firstName' => $shippingAddress->getFirstname(),
                    'lastName' => $shippingAddress->getLastname(),
                    'address' => $shippingAddress->getStreet(1),
                    'address2' => $shippingAddress->getStreet(2),
                    'zipCode' => $shippingAddress->getPostcode(),
                    'city' => $shippingAddress->getCity(),
                    'state' => !is_numeric($shippingAddress->getRegionCode()) ?
                        $shippingAddress->getRegionCode() : $shippingAddress->getRegion(),
                    'phoneNumber' => $shippingAddress->getTelephone(),
                    'country' => $shippingAddress->getCountryId(),
                    'deliveryCompanyName' => $methodCode['oney_label'],
                    'shippingMethod' => $methodCode['type'],
                    'shippingSpeed' => $methodCode['speed']
                )
            ),
            'transactionOptions' => array(
                'cardOptions' => array(
                    'captureDelay' => $captureDelay,
                    'manualValidation' => $validationMode ? 'YES' : 'NO',
                    'paymentSource' => 'EC'
                )
            ),
            'contrib' => $contrib . Mage::getVersion() . '/' . Lyranetwork_Payzen_Model_Api_Api::shortPhpVersion(),
            'strongAuthentication' => $strongAuth,
            'currency' => $currency->getAlpha3(),
            'amount' => $currency->convertAmountToInteger($amount),
            'metadata' => array(
                'quote_id' => $quote->getId()
            )
        );

        // Set Number of attempts in case of rejected payment.
        if ($this->getConfigData('rest_attempts')) {
            $data['transactionOptions']['cardOptions']['retry'] = $this->getConfigData('rest_attempts');
        }

        $customer = $quote->getCustomer();

        if ($useIdentifier && Mage::getSingleton('checkout/session')->getValidAlias()) {
            $this->_getHelper()->log("Customer {$customer->getEmail()} has an identifier. Use it for payment of order #{$quote->getReservedOrderId()}.");
            $data['paymentMethodToken'] = $customer->getPayzenIdentifier();
        } elseif ($this->getConfigData('one_click_active') && $quote->getCustomer()->getId()) {
            // 1-Click enabled and customer logged-in, let's ask customer for card data registration.
            $this->_getHelper()->log("Customer {$customer->getEmail()} will be asked for card data registration on payment page for order #{$quote->getReservedOrderId()}.");
            $data['formAction'] = 'ASK_REGISTER_PAY';
        }

        return $data;
    }

    public function getFormToken($useIdentifier, $renew = false)
    {
        $token = false;

        $session = Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();

        if (! $quote || ! $quote->getId() || ($quote->getGrandTotal() <= 0)) {
            $this->_getHelper()->log('Cannot create form token. Empty quote passed.');
            return false;
        }

        // If error when creating form token, we will force redirection.
        if ($session->getPayzenForceRedirection()) {
            return false;
        }

        $data = $this->_getFormTokenData($quote, $useIdentifier);

        $tokenDataName = Lyranetwork_Payzen_Helper_Payment::TOKEN_DATA . ($useIdentifier ? '_identifier' : '');
        $tokenName = Lyranetwork_Payzen_Helper_Payment::TOKEN . ($useIdentifier ? '_identifier' : '');

        if ($renew) {
            $quote->getPayment()->unsAdditionalInformation($tokenDataName);
            $quote->getPayment()->unsAdditionalInformation($tokenName);
        } else {
            $lastTokenData = $quote->getPayment()->getAdditionalInformation($tokenDataName);
            $lastToken = $quote->getPayment()->getAdditionalInformation($tokenName);

            $tokenData = base64_encode(serialize($data));
            if ($lastToken && ($lastTokenData === $tokenData)) {
                 // Cart data does not change from last payment attempt, do not re-create payment token.
                 $this->_getHelper()->log("Cart data does not change from last payment attempt, use last created token for quote #{$quote->getId()}, reserved order ID #{$quote->getReservedOrderId()}.");
                 return $lastToken;
            }
        }

        $login = $this->_getHelper()->getCommonConfigData('site_id');

        try {
            // Perform our request.
            $client = new Lyranetwork_Payzen_Model_Api_Rest(
                $this->_getHelper()->getCommonConfigData('rest_url'),
                $login,
                $this->_getRestHelper()->getPassword()
            );

            $response = $client->post('V4/Charge/CreatePayment', json_encode($data));

            if ($response['status'] !== 'SUCCESS') {
                $msg = "Error while creating payment form token for quote #{$quote->getId()}, reserved order ID #{$quote->getReservedOrderId()}: ";
                $msg .= $response['answer']['errorMessage'] . ' (' . $response['answer']['errorCode'] . ').';
                $this->_getHelper()->log($msg);

                if (isset($response['answer']['detailedErrorMessage']) && ! empty($response['answer']['detailedErrorMessage'])) {
                    $this->_getHelper()->log('Detailed message: ' . $response['answer']['detailedErrorMessage']
                        . ' (' . $response['answer']['detailedErrorCode'] . ').');
                }

                $token = false;
            } else {
                // Payment form token created successfully.
                $token = $response['answer']['formToken'];
                $tokenData = base64_encode(serialize($data));

                $this->_getHelper()->log("Form token created successfully for quote #{$quote->getId()}, reserved order ID #{$quote->getReservedOrderId()}.");

                $quote->getPayment()->setAdditionalInformation($tokenDataName, $tokenData);
                $quote->getPayment()->setAdditionalInformation($tokenName, $token);
                $quote->getPayment()->save();
            }
        } catch (Exception $e) {
            $this->_getHelper()->log($e->getMessage());
            $token = false;
        }

        if (! $token) {
            $session->setPayzenForceRedirection(true);
        }

        return $token;
    }

    /**
     * Return available card types.
     *
     * @return string
     */
    public function getAvailableCcTypes()
    {
        // All cards.
        $allCards = Lyranetwork_Payzen_Model_Api_Api::getSupportedCardTypes();

        // Selected cards from module configuration.
        $cards = $this->getConfigData('payment_cards');

        if (! empty($cards)) {
            $cards = explode(',', $cards);
        } else {
            $cards = array_keys($allCards);
            $cards = array_diff($cards, array('ONEY_SANDBOX', 'ONEY'));
        }

        if (! $this->_getHelper()->isAdmin() && $this->isLocalCcType()
            && $this->getConfigData('use_oney_in_standard')
        ) {
            $testMode = $this->_getHelper()->getCommonConfigData('ctx_mode') === 'TEST';

            $cards[] = $testMode ? 'ONEY_SANDBOX' : 'ONEY';
        }

        $availCards = array();
        foreach ($allCards as $code => $label) {
            if (in_array($code, $cards)) {
                $availCards[$code] = $label;
            }
        }

        return $availCards;
    }

    public function isOneclickAvailable()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        // Rest API mode active.
        if ($this->isEmbedded()) {
            return false;
        }

        // Payment by token not active.
        if (! $this->getConfigData('one_click_active')) {
            return false;
        }

        if ($this->_getHelper()->isAdmin()) {
            return false;
        }

        $session = Mage::getSingleton('customer/session');

        // Customer not logged in.
        if (! $session->isLoggedIn()) {
            return false;
        }

        // Customer has not gateway identifier.
        $customer = $session->getCustomer();
        if (! $customer || ! $customer->getPayzenIdentifier()) {
            return false;
        }

        return true;
    }

    /**
     * Assign data to info model instance.
     *
     * @param  mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (! ($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        $useIdentifier = $data->getPayzenStandardUseIdentifier();

        $info->setCcType($data->getPayzenStandardCcType())
            ->setCcLast4(null)
            ->setCcNumber(null)
            ->setCcCid(null)
            ->setCcExpMonth(null)
            ->setCcExpYear(null)
            ->setAdditionalData(null)
            ->setAdditionalInformation(Lyranetwork_Payzen_Helper_Payment::IDENTIFIER, $useIdentifier); // Payment by identifier.

        Mage::getSingleton('checkout/session')->setIdentifierPayment($useIdentifier);

        return $this;
    }

    /**
     * Prepare info instance for save.
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        $info->setCcNumberEnc(null);
        $info->setCcNumber(null);
        $info->setCcCid(null);

        return $this;
    }

    /**
     * The URL the customer is redirected to after clicking on "Confirm order".
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        if ($this->isIframeMode()) {
            return Mage::getUrl('payzen/payment/iframe', array('_secure' => true));
        }

        return parent::getOrderPlaceRedirectUrl();
    }

    /**

     * Return true if iframe mode is enabled.
     *
     * @return string
     */
    public function isIframeMode()
    {
        return $this->getConfigData('card_info_mode') == Lyranetwork_Payzen_Helper_Data::MODE_IFRAME;
    }

    /**
     * Check if the local card type selection option is choosen.
     *
     * @return boolean
     */
    public function isLocalCcType()
    {
        return $this->getConfigData('card_info_mode') == Lyranetwork_Payzen_Helper_Data::MODE_LOCAL_TYPE;
    }

    /**
     * Check if the embedded payment fields option is choosen.
     *
     * @return boolean
     */
    public function isEmbedded() {
        $embedded = array(
            Lyranetwork_Payzen_Helper_Data::MODE_EMBEDDED,
            Lyranetwork_Payzen_Helper_Data::MODE_POPIN
        );

        return in_array($this->getConfigData('card_info_mode'), $embedded);
    }
}
