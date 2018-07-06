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

class Lyra_Payzen_Helper_Util extends Mage_Core_Helper_Abstract
{
    const ORDER_ID_REGEX = '#^[a-zA-Z0-9]{1,9}$#u';
    const CUST_ID_REGEX = '#^[a-zA-Z0-9]{1,8}$#u';

    const PRODUCT_REF_REGEX = '#^[a-zA-Z0-9]{1,64}$#u';

    public static $address_regex = array(
        'oney' => array(
            'name' => "#^[A-ZÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '-]{1,63}$#ui",
            'street' => "#^[A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '.,-]{1,127}$#ui",
            'zip' => '#^[0-9]{5}$#',
            'city' => "#^[A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '-]{1,127}$#ui",
            'country' => '#^FR|GP|MQ|GF|RE|YT$#i',
            'phone' => '#^[0-9]{10}$#'
        ),
        'fullcb' => array(
            'name' => "#^[A-Za-z0-9àâçèéêîôùû]+([ \-']?[A-Za-z0-9àâçèéêîôùû]+)*$#",
            'street' => '#^[^;]*$#',
            'zip' => '#^[0-9]{5}$#',
            'city' => '#^[^;]*$#',
            'country' => '#^FR$#',
            'phone' => '#^(0|33)[0-9]{9}$#'
        )
    );

    /**
     * Normalize shipping method name.
     *
     * @param string $name
     * @return string normalized name
     */
    public function normalizeShipMethodName($name)
    {
        $notAllowed = "#[^A-ZÇ0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ /'-]#ui";

        return preg_replace($notAllowed, '', $name);
    }

    public function checkCustormers($scope, $scopeId)
    {
        // check customer IDs
        $collection = Mage::getModel('customer/customer')->getCollection();

        if ($scope == 'websites') {
            $collection->addAttributeToFilter('website_id', $scopeId);
        } elseif ($scope == 'stores') {
            $collection->addAttributeToFilter('store_id', $scopeId);
        }

        $iterator = Mage::getSingleton('core/resource_iterator');
        $iterator->walk($collection->getSelect(), array(array($this, 'checkCustormer')));
    }

    public function checkCustormer($args)
    {
        $customer = Mage::getModel('customer/customer');
        $customer->setData($args['row']);

        if (! preg_match(self::CUST_ID_REGEX, $customer->getId())) {
            // a customer id doesn't match PayZen rules

            $msg = '';
            $msg .= $this->_getHelper()->__(
                'Customer ID &laquo;%s&raquo; does not match PayZen specifications.',
                $customer->getId()
            );
            $msg .= ' ';
            $msg .= $this->_getHelper()->__(
                'This field must agree to the regular expression %s.',
                self::CUST_ID_REGEX
            );

            Mage::throwException($msg);
        }
    }

    public function checkOrders($scope, $scopeId)
    {
        // check order IDs
        if ($scope == 'stores') {
            // store context
            $incrementId = Mage::getSingleton('eav/config')->getEntityType('order')
                ->fetchNewIncrementId($scopeId);

            $this->_checkOrderId($incrementId);
        } else {
            // general and website context
            $stores = Mage::app()->getStores();

            foreach ($stores as $store) {
                if ($scope == 'websites' && $store->getWebsiteId() != $scopeId) {
                    continue;
                }

                $incrementId = Mage::getSingleton('eav/config')->getEntityType('order')
                    ->fetchNewIncrementId($store->getId());
                $this->_checkOrderId($incrementId);
            }
        }
    }

    protected function _checkOrderId($orderId)
    {
        if (! preg_match(self::ORDER_ID_REGEX, $orderId)) {
            // the potential next order id doesn't match PayZen rules

            $msg = '';
            $msg .= $this->_getHelper()->__(
                'The next order ID  &laquo;%s&raquo; does not match PayZen specifications.',
                $orderId
            ) . ' ';
            $msg .= $this->_getHelper()->__(
                'This field must agree to the regular expression %s.',
                self::ORDER_ID_REGEX
            );

            Mage::throwException($msg);
        }
    }

    public function checkProducts($scope, $scopeId)
    {
        // check products' IDs and labels
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect('name');

        if ($scope == 'websites') {
            $collection->addWebsiteFilter($scopeId);
        } elseif ($scope == 'stores') {
            $collection->addStoreFilter($scopeId);
        }

        $iterator = Mage::getSingleton('core/resource_iterator');
        $iterator->walk($collection->getSelect(), array(array($this, 'checkProduct')));
    }

    public function checkProduct($args)
    {
        $product = Mage::getModel('catalog/product');
        $product->setData($args['row']);

        if (! preg_match(self::PRODUCT_REF_REGEX, $product->getId())) {
            // product id doesn't match PayZen rules

            $msg = '';
            $msg .= $this->_getHelper()->__(
                'Product reference &laquo;%s&raquo; does not match PayZen specifications.',
                $product->getId()
            );
            $msg .= ' ';
            $msg .= $this->_getHelper()->__(
                'This field must agree to the regular expression %s.',
                self::PRODUCT_REF_REGEX
            );

            Mage::throwException($msg);
        }
    }

    public function checkOneyRequirements($scope, $scopeId)
    {
        $this->checkOrders($scope, $scopeId);
        $this->checkCustormers($scope, $scopeId);
        $this->checkProducts($scope, $scopeId);
    }

    public function toPayzenCarrier($methodCode)
    {
        $matches = array();

        if (preg_match('#^(pointsrelais[a-z0-9]*)_.+$#i', $methodCode, $matches)) {
            // Modial Relay carrier create shipping methods dynamically (after WS call)
            $methodCode = $matches[1] . '_pointsrelais';
        }

        $shippingMapping = unserialize($this->_getHelper()->getCommonConfigData('ship_options'));

        if (is_array($shippingMapping) && ! empty($shippingMapping)) {
            foreach ($shippingMapping as $id => $shippingMethod) {
                if ($shippingMethod['code'] === $methodCode) {
                    return $shippingMethod;
                }
            }
        }

        return null;
    }

    public function toPayzenCategory($categoryIds)
    {
        // commmon category if any
        $commonCategory = $this->_getHelper()->getCommonConfigData('common_category');
        if ($commonCategory != 'CUSTOM_MAPPING') {
            return $commonCategory;
        }

        $categoryMapping = unserialize($this->_getHelper()->getCommonConfigData('category_mapping'));

        if (is_array($categoryMapping) && ! empty($categoryMapping) && is_array($categoryIds) && ! empty($categoryIds)) {
            foreach ($categoryMapping as $id => $aCategory) {
                if (in_array($aCategory['code'], $categoryIds)) {
                    return $aCategory['payzen_category'];
                }
            }

            $category = Mage::getModel('catalog/category')->load($categoryIds[0]);
            foreach ($categoryMapping as $id => $aCategory) {
                if (in_array($aCategory['code'], $category->getParentIds())) {
                    return $aCategory['payzen_category'];
                }
            }
        }

        return null;
    }

    public function setCartData($order, &$payzenRequest)
    {
        $notAllowed = '#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ ]#ui';

        // used currency
        $currency = Lyra_Payzen_Model_Api_Api::findCurrencyByNumCode($payzenRequest->get('currency'));

        $subtotal = 0;

        // load all products in the shopping cart
        foreach ($order->getAllItems() as $item) {
            // check to avoid sending the whole hierarchy of a configurable product
            if (! $item->getParentItem()) {
                $product = $item->getProduct();
                if (! $product) {
                    // load product instance
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                }

                $label = $item->getName();

                // concat product label with one or two of its category names to make it clearer
                $categoryIds = $product->getCategoryIds();
                if (is_array($categoryIds) && ! empty($categoryIds)) {
                    if (isset($categoryIds[1]) && $categoryIds[1]) {
                        $category = Mage::getModel('catalog/category')->load($categoryIds[1]);
                        $label = $category->getName() . ' I ' . $label;
                    }

                    if ($categoryIds[0]) {
                        $category = Mage::getModel('catalog/category')->load($categoryIds[0]);
                        $label = $category->getName() . ' I ' . $label;
                    }
                }

                $priceInCents = $currency->convertAmountToInteger($item->getPrice());
                $qty = (int) $item->getQtyOrdered();

                $payzenRequest->addProduct(
                    preg_replace($notAllowed, ' ', $label),
                    $priceInCents,
                    $qty,
                    $item->getProductId(),
                    $this->toPayzenCategory($categoryIds)
                );

                $subtotal += $priceInCents * $qty;
            }
        }

        $payzenRequest->set('insurance_amount', 0); // by default, shipping insurance amount is not available in Magento
        $payzenRequest->set('shipping_amount', $currency->convertAmountToInteger($order->getShippingAmount()));

        // recalculate tax_amount to avoid rounding problems
        $taxAmount = $payzenRequest->get('amount') - $subtotal - $payzenRequest->get('shipping_amount')
            - $payzenRequest->get('insurance_amount');
        if ($taxAmount <= 0) { // when order is discounted
            $taxAmount = $currency->convertAmountToInteger($order->getTaxAmount());
        }

        $payzenRequest->set('tax_amount', $taxAmount);
    }

    public function setAdditionalShippingData($order, &$payzenRequest, $useOney = false)
    {
        // by default, clients are protected
        $payzenRequest->set('cust_status', 'PRIVATE');
        $payzenRequest->set('ship_to_status', 'PRIVATE');

        $notAllowedCharsRegex = "#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ /'-]#ui";

        if ($order->getIsVirtual() || ! $order->getShippingMethod()) { // there is no shipping method
            // set store name after illegal characters replacement
            $payzenRequest->set(
                'ship_to_delivery_company_name',
                preg_replace($notAllowedCharsRegex, ' ', Mage::app()->getStore()->getFrontendName())
            );
            $payzenRequest->set('ship_to_type', 'ETICKET');
            $payzenRequest->set('ship_to_speed', 'EXPRESS');
        } else {
            $shippingMethod = $this->toPayzenCarrier($order->getShippingMethod());

            // delivery point name
            $name = '';

            switch ($shippingMethod['type']) {
                case 'RELAY_POINT':
                case 'RECLAIM_IN_STATION':

                    if (strpos($shippingMethod['code'], 'pointsrelais') === 0 && $order->getShippingAddress()->getCompany()) { // Modial Relay ColisDrive or relay point
                        $name = $order->getShippingAddress()->getCompany();
                    } else {
                        $name = $order->getShippingDescription();

                        $carrierCode = substr($shippingMethod['code'], 0, strpos($shippingMethod['code'], '_'));
                        $carrierName = Mage::getStoreConfig('carriers/' . $carrierCode . '/title');
                        if ($carrierName) {
                            $name = str_replace($carrierName . ' - ', '', $name);
                        }

                        $name = substr($name, 0, strpos($name, '<')); // remove HTML elements
                    }

                    // break intentionally omitted

                case 'RECLAIM_IN_SHOP':

                    if ($useOney) {
                        // modify address to send it to Oney server
                        $address = '';
                        $address .= $name;
                        $address .= $order->getShippingAddress()->getStreet(1);
                        $address .= $order->getShippingAddress()->getStreet(2) ?
                            ' ' . $order->getShippingAddress()->getStreet(2) : '';

                        $payzenRequest->set('ship_to_street', $address);
                        $payzenRequest->set('ship_to_zip', $order->getShippingAddress()->getPostcode());
                        $payzenRequest->set('ship_to_city', $order->getShippingAddress()->getCity());
                        $payzenRequest->set('ship_to_street2', null); // not sent to FacilyPay Oney
                        $payzenRequest->set('ship_to_state', null); // not sent to FacilyPay Oney

                        // send FR even address is in DOM-TOM unless form is rejected
                        $payzenRequest->set('cust_country', 'FR');
                        $payzenRequest->set('ship_to_country', 'FR');
                    }

                    if (empty($name)) {
                        $name = substr($order->getShippingDescription(), 0, 55);
                    }

                    // send delivery point name, address, postcode and city in field ship_to_delivery_company_name
                    $name .= $order->getShippingAddress()->getStreet(1);
                    $name .= $order->getShippingAddress()->getStreet(2) ?
                        ' ' . $order->getShippingAddress()->getStreet(2) : '';
                    $name .= ' ' . $order->getShippingAddress()->getPostcode();
                    $name .= ' ' . $order->getShippingAddress()->getCity();

                    // delete not allowed chars
                    $name = preg_replace($notAllowedCharsRegex, ' ', $name);
                    $payzenRequest->set('ship_to_delivery_company_name', $name);
                    break;

                default :
                    if ($useOney) {
                        // modify address to send it to Oney server
                        $address = '';
                        $address .= $order->getShippingAddress()->getStreet(1);
                        $address .= $order->getShippingAddress()->getStreet(2) ?
                            ' ' . $order->getShippingAddress()->getStreet(2) : '';

                        $payzenRequest->set('ship_to_street', $address);
                        $payzenRequest->set('ship_to_street2', null); // not sent to FacilyPay Oney

                        // send FR even address is in DOM-TOM unless form is rejected
                        $payzenRequest->set('cust_country', 'FR');
                        $payzenRequest->set('ship_to_country', 'FR');
                    }

                    $payzenRequest->set('ship_to_delivery_company_name', $shippingMethod['oney_label']);
                    break;
            }

            $payzenRequest->set('ship_to_type', $shippingMethod['type']);
            $payzenRequest->set('ship_to_speed', $shippingMethod['speed']);

            if ($shippingMethod['speed'] === 'PRIORITY') {
                $payzenRequest->set('ship_to_delay', $shippingMethod['delay']);
            }
        }
    }

    public function checkAddressValidity($address, $payment)
    {
        if (! $address) {
            return;
        }

        $regex = self::$address_regex[$payment];

        // specific validation regular expressions
        $nameRegex = $regex['name'];
        $phoneRegex = $regex['phone'];
        $cityRegex = $regex['city'];
        $streetRegex = $regex['street'];
        $countryRegex = $regex['country'];
        $zipRegex = $regex['zip'];

        // error messages
        $invalidMsg = 'The field %s of your %s is invalid.';
        $emptyMsg = 'The field %s of your %s is mandatory.';

        // address type
        $addressType = ($address->getAddressType() === 'billing') ? 'billing address' : 'delivery address';

        if (! $address->getLastname()) {
            $this->_throwException($emptyMsg, 'Last Name', $addressType);
        } elseif (! preg_match($nameRegex, $address->getLastname())) {
            $this->_throwException($invalidMsg, 'Last Name', $addressType);
        } elseif (! $address->getFirstname()) {
            $this->_throwException($emptyMsg, 'First Name', $addressType);
        } elseif (! preg_match($nameRegex, $address->getFirstname())) {
            $this->_throwException($invalidMsg, 'First Name', $addressType);
        } elseif ($address->getTelephone() && ! preg_match($phoneRegex, $address->getTelephone())) {
            $this->_throwException($invalidMsg, 'Telephone', $addressType);
        } elseif (! $address->getStreet(1)) {
            $this->_throwException($emptyMsg, 'Address', $addressType);
        } elseif (! preg_match($streetRegex, $address->getStreet(1))) {
            $this->_throwException($invalidMsg, 'Address', $addressType);
        } elseif ($address->getStreet(2) && ! preg_match($streetRegex, $address->getStreet(2))) {
            $this->_throwException($invalidMsg, 'Address', $addressType);
        } elseif (! $address->getPostcode()) {
            $this->_throwException($emptyMsg, 'Postcode', $addressType);
        } elseif (! preg_match($zipRegex, $address->getPostcode())) {
            $this->_throwException($invalidMsg, 'Postcode', $addressType);
        } elseif (! $address->getCity()) {
            $this->_throwException($emptyMsg, 'City', $addressType);
        } elseif (! preg_match($cityRegex, $address->getCity())) {
            $this->_throwException($invalidMsg, 'City', $addressType);
        } elseif (! $address->getCountryId()) {
            $this->_throwException($emptyMsg, 'Country', $addressType);
        } elseif (! preg_match($countryRegex, $address->getCountryId())) {
            $this->_throwException($invalidMsg, 'Country', $addressType);
        }
    }

    protected function _throwException($msg, $field, $addressType)
    {
        // translate
        $field = $this->_getHelper()->__($field);
        $addressType = $this->_getHelper()->__($addressType);

        Mage::throwException($this->_getHelper()->__($msg, $field, $addressType));
    }

    protected function _getHelper()
    {
        return Mage::helper('payzen');
    }
}
