<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Helper;

use Lyranetwork\Payzen\Model\Api\PayzenApi;

class Checkout
{

    const ORDER_ID_REGEX = '#^[a-zA-Z0-9]{1,9}$#';

    const CUST_ID_REGEX = '#^[a-zA-Z0-9]{1,8}$#';

    const PRODUCT_REF_REGEX = '#^[a-zA-Z0-9]{1,64}$#';

    /**
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     *
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     *
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;

    /**
     *
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Lyranetwork\Payzen\Helper\Data $dataHelper
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->eavConfig = $eavConfig;
        $this->storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Normalize shipping method name.
     *
     * @param string $name
     * @return string normalized name
     */
    public function cleanShippingMethod($name)
    {
        $notAllowed = "#[^A-ZÇ0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ /'-]#ui";
        return preg_replace($notAllowed, '', $name);
    }

    public function checkCustormers($scope, $scopeId)
    {
        // Check customer IDs.
        $collection = $this->customerCollectionFactory->create();

        if ($scope == 'websites') {
            $collection->addAttributeToFilter('website_id', $scopeId);
        } elseif ($scope == 'stores') {
            $collection->addAttributeToFilter('store_id', $scopeId);
        }
        $collection->load();

        foreach ($collection as $customer) {
            if (! preg_match(self::CUST_ID_REGEX, $customer->getId())) {
                // A customer ID doesn't match gateway rules.

                $msg = '';
                $msg .= __(
                    'Customer ID &laquo; %1 &raquo; does not match PayZen specifications.',
                    $customer->getId()
                )->render() . ' ';
                $msg .= __('This field must agree to the regular expression %1.', self::CUST_ID_REGEX)->render();

                throw new \Magento\Framework\Exception\LocalizedException(__($msg));
            }
        }
    }

    public function checkOrders($scope, $scopeId)
    {
        // Check order IDs.
        if ($scope == 'stores') {
            // Store context.
            $incrementId = $this->eavConfig->getEntityType(\Magento\Sales\Model\Order::ENTITY)->fetchNewIncrementId(
                $scopeId
            );

            $this->checkOrderId($incrementId);
        } else {
            // General and website context.
            $stores = $this->storeManager->getStores();

            foreach ($stores as $store) {
                if ($scope == 'websites' && $store->getWebsiteId() != $scopeId) {
                    continue;
                }

                $incrementId = $this->eavConfig->getEntityType(\Magento\Sales\Model\Order::ENTITY)->fetchNewIncrementId(
                    $store->getId()
                );
                $this->checkOrderId($incrementId);
            }
        }
    }

    private function checkOrderId($orderId)
    {
        if (! preg_match(self::ORDER_ID_REGEX, $orderId)) {
            // The potential next order id doesn't match gateway rules.

            $msg = '';
            $msg .= __(
                'The next order ID  &laquo; %1 &raquo; does not match PayZen specifications.',
                $orderId
            )->render() . ' ';
            $msg .= __('This field must agree to the regular expression %1.', self::ORDER_ID_REGEX)->render();

            throw new \Magento\Framework\Exception\LocalizedException(__($msg));
        }
    }

    public function checkProducts($scope, $scopeId)
    {
        // Check products' IDs and labels.
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('name');

        if ($scope == 'websites') {
            $collection->addWebsiteFilter($scopeId);
        } elseif ($scope == 'stores') {
            $collection->addStoreFilter($scopeId);
        }
        $collection->load();

        foreach ($collection as $product) {
            if (! preg_match(self::PRODUCT_REF_REGEX, $product->getId())) {
                // Product id doesn't match gateway rules.

                $msg = '';
                $msg .= __(
                    'Product reference &laquo; %1 &raquo; does not match PayZen specifications.',
                    $product->getId()
                )->render() . ' ';
                $msg .= __('This field must agree to the regular expression %1.', self::PRODUCT_REF_REGEX)->render();

                throw new \Magento\Framework\Exception\LocalizedException(__($msg));
            }
        }
    }

    public function checkOneyRequirements($scope, $scopeId)
    {
        $this->checkOrders($scope, $scopeId);
        $this->checkCustormers($scope, $scopeId);
        $this->checkProducts($scope, $scopeId);
    }

    public function toOneyCarrier($methodCode)
    {
        $shippingMapping = $this->dataHelper->unserialize($this->dataHelper->getCommonConfigData('ship_options'));

        if (is_array($shippingMapping) && ! empty($shippingMapping)) {
            foreach ($shippingMapping as $shippingMethod) {
                if ($shippingMethod['code'] === $methodCode) {
                    return $shippingMethod;
                }
            }
        }

        return null;
    }

    public function toPayzenCategory($categoryIds)
    {
        // Commmon category if any.
        $commonCategory = $this->dataHelper->getCommonConfigData('common_category');
        if ($commonCategory != 'CUSTOM_MAPPING') {
            return $commonCategory;
        }

        $categoryMapping = $this->dataHelper->unserialize($this->dataHelper->getCommonConfigData('category_mapping'));

        if (is_array($categoryMapping) && ! empty($categoryMapping) && is_array($categoryIds) && ! empty($categoryIds)) {
            foreach ($categoryMapping as $category) {
                if (in_array($category['code'], $categoryIds)) {
                    return $category['payzen_category'];
                }
            }
        }

        return null;
    }

    public function setCartData($order, &$payzenRequest)
    {
        $notAllowed = '#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ ]#ui';

        // Used currency.
        $currency = PayzenApi::findCurrencyByNumCode($payzenRequest->get('currency'));

        $subtotal = 0;

        // Load all products in the shopping cart.
        foreach ($order->getAllItems() as $item) {
            // Check to avoid sending the whole hierarchy of a configurable product.
            if (! $item->getParentItem()) {
                $product = $item->getProduct();

                $label = $item->getName();

                // Concat product label with one or two of its category names to make it clearer.
                $categoryIds = $product->getCategoryIds();
                if (is_array($categoryIds) && ! empty($categoryIds)) {
                    if (isset($categoryIds[1]) && $categoryIds[1]) {
                        $category = $this->categoryRepository->get($categoryIds[1]);
                        $label = $category->getName() . ' I ' . $label;
                    }

                    if ($categoryIds[0]) {
                        $category = $this->categoryRepository->get($categoryIds[0]);
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
                    $this->toPayzenCategory($product->getCategoryIds())
                );

                $subtotal += $priceInCents * $qty;
            }
        }

        $payzenRequest->set('insurance_amount', 0); // By default, shipping insurance amount is not available in Magento.
        $payzenRequest->set('shipping_amount', $currency->convertAmountToInteger($order->getShippingAmount()));

        // Recalculate tax_amount to avoid rounding problems.
        $taxAmount = $payzenRequest->get('amount') - $subtotal - $payzenRequest->get('shipping_amount') -
             $payzenRequest->get('insurance_amount');
        if ($taxAmount <= 0) { // When order is discounted.
            $taxAmount = $currency->convertAmountToInteger($order->getTaxAmount());
        }

        $payzenRequest->set('tax_amount', $taxAmount);

        // VAT amount for colombian payment means.
        $payzenRequest->set('totalamount_vat', $taxAmount);
    }

    public function setOneyData($order, &$payzenRequest)
    {
        // By default, clients are private.
        $payzenRequest->set('cust_status', 'PRIVATE');
        $payzenRequest->set('ship_to_status', 'PRIVATE');

        $notAllowedCharsRegex = "#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ /'-]#ui";

        if ($order->getIsVirtual() || ! $order->getShippingMethod()) {
            // There is no shipping mean set store name after illegal characters replacement.

            $storeId = $this->dataHelper->getCheckoutStoreId();
            $payzenRequest->set(
                'ship_to_delivery_company_name',
                preg_replace($notAllowedCharsRegex, ' ', $this->dataHelper->getStore($storeId)->getFrontendName())
            );

            $payzenRequest->set('ship_to_type', 'ETICKET');
            $payzenRequest->set('ship_to_speed', 'EXPRESS');
        } else {
            $shippingMethod = $this->toOneyCarrier($order->getShippingMethod());

            switch ($shippingMethod['type']) {
                case 'RECLAIM_IN_SHOP':
                case 'RELAY_POINT':
                case 'RECLAIM_IN_STATION':
                    // It's recommended to put a specific logic here.

                    $address = ''; // Initialize with selected SHOP/RELAY POINT/STATION name.
                    $address .= $order->getShippingAddress()->getStreet(1);
                    $address .= $order->getShippingAddress()->getStreet(2) ? ' ' .
                         $order->getShippingAddress()->getStreet(2) : '';

                    $payzenRequest->set('ship_to_street', $address);
                    $payzenRequest->set('ship_to_zip', $order->getShippingAddress()->getPostcode());
                    $payzenRequest->set('ship_to_city', $order->getShippingAddress()->getCity());
                    $payzenRequest->set('ship_to_country', 'FR');
                    $payzenRequest->set('ship_to_street2', null); // Not sent to FacilyPay Oney.
                    $payzenRequest->set('ship_to_state', null);
                    $payzenRequest->set('ship_to_phone_num', null);

                    // Add postcode and city to send them in ship_to_delivery_company_name.
                    $address .= ' ' . $order->getShippingAddress()->getPostcode();
                    $address .= ' ' . $order->getShippingAddress()->getCity();

                    // Delete not allowed chars.
                    $address = preg_replace($notAllowedCharsRegex, ' ', $address);
                    $method = $shippingMethod['oney_label'] . ' ' . $address;
                    $payzenRequest->set('ship_to_delivery_company_name', $method);
                    break;
                default:
                    $address = '';
                    $address .= $order->getShippingAddress()->getStreet(1);
                    $address .= $order->getShippingAddress()->getStreet(2) ? ' ' .
                         $order->getShippingAddress()->getStreet(2) : '';

                    $payzenRequest->set('ship_to_street', $address);
                    $payzenRequest->set('ship_to_street2', null); // Not sent to FacilyPay Oney.

                    $payzenRequest->set('ship_to_delivery_company_name', $shippingMethod['oney_label']);
                    break;
            }

            $payzenRequest->set('ship_to_type', $shippingMethod['type']);
            $payzenRequest->set('ship_to_speed', $shippingMethod['speed']);
        }
    }

    public function checkAddressValidity($address)
    {
        if (! $address) {
            return;
        }

        // Oney validation regular expressions.
        $nameRegex = "#^[A-ZÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '-]{1,63}$#ui";
        $phoneRegex = "#^[0-9]{10}$#";
        $cityRegex = "#^[A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '-]{1,127}$#ui";
        $streetRegex = "#^[A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ/ '.,-]{1,127}$#ui";
        $countryRegex = "#^FR$#i";
        $zipRegex = "#^[0-9]{5}$#";

        // Address type.
        $addressType = ($address->getAddressType() === 'billing') ? 'billing address' : 'delivery address';

        $this->checkFieldValidity($address->getLastname(), $nameRegex, 'Last Name', $addressType);
        $this->checkFieldValidity($address->getFirstname(), $nameRegex, 'First Name', $addressType);
        $this->checkFieldValidity($address->getTelephone(), $phoneRegex, 'Telephone', $addressType, false);
        $this->checkFieldValidity($address->getStreet(1), $streetRegex, 'Address', $addressType);
        $this->checkFieldValidity($address->getStreet(2), $streetRegex, 'Address', $addressType, false);
        $this->checkFieldValidity($address->getPostcode(), $zipRegex, 'Postcode', $addressType);
        $this->checkFieldValidity($address->getCity(), $cityRegex, 'City', $addressType);
        $this->checkFieldValidity($address->getCountryId(), $countryRegex, 'Country', $addressType);
    }

    private function checkFieldValidity($field, $regex, $fieldName, $addressType, $mandatory = true)
    {
        // Error messages.
        $invalidMsg = 'The field %1 of your %2 is invalid.';
        $emptyMsg = 'The field %1 of your %2 is mandatory.';

        if ($mandatory && ! $field) {
            $this->throwException($emptyMsg, $fieldName, $addressType);
        }

        if ($field && ! preg_match($regex, $field)) {
            $this->throwException($invalidMsg, $fieldName, $addressType);
        }
    }

    private function throwException($msg, $field, $addressType)
    {
        // Translate.
        $field = __($field);
        $addressType = __($addressType);

        throw new \Magento\Framework\Exception\LocalizedException(__($msg, $field, $addressType));
    }
}
