<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Observer
{
    public function doPaymentRedirect($observer)
    {
        if (! $this->_getHelper()->isAdmin()) {
            // Not an admin-passed order, do nothing.
            return;
        }

        $order = $observer->getOrder();

        if (! $order || $order->getId() <= 0) {
            // Order creation failed.
            return;
        }

        $method = $order->getPayment()->getMethodInstance();

        if ($method instanceof Lyranetwork_Payzen_Model_Payment_Abstract) {
            // Backend payment with redirection.

            $flag = false;
            if ($data = Mage::app()->getRequest()->getPost('order')) {
                $flag = isset($data['send_confirmation']) ? (bool) $data['send_confirmation'] : false;
            }

            $session = Mage::getSingleton('adminhtml/session_quote');

            $session->setPayzenCanSendNewEmail($flag); // Flag that allows sending new order email.

            $session->setQuoteId($observer->getQuote()->getId())
                ->setLastSuccessQuoteId($observer->getQuote()->getId())
                ->setLastRealOrderId($order->getIncrementId());

            session_write_close();

            $redirectUrl = $this->_getHelper()->prepareUrl('adminhtml/payzen_payment/form', 0, true);
            Mage::app()->getResponse()->setRedirect($redirectUrl)->sendHeadersAndExit();
        }
    }

    public function doPaymentMultiUpdate($observer)
    {
        $payment = $observer->getDataObject();

        if ($payment->getMethod() != 'payzen_multi') {
            // Not multiple payment, do nothing.
            return;
        }

        // Retreive selected option.
        $option = @unserialize($payment->getAdditionalData());
        if (isset($option) && is_array($option)) {
            $payment->setMethod('payzen_multi_' . $option['count'] . 'x');
        }
    }

    public function doPaymentMethodColumnAppend($observer)
    {
        /* @var $block Mage_Adminhtml_Block_Sales_Order_Grid */
        $block = $observer->getBlock();

        if (isset($block) && ($block->getType() === 'adminhtml/sales_order_grid')) {
            $availableMethods = Mage::getStoreConfig('payment');

            if (! $block->getColumn('payment_method')) {
                $groupedMethods = array();
                $methods = array();

                foreach ($availableMethods as $code => $method) {
                    if (! is_array($method) || ! isset($method['model'])) {
                        continue;
                    }

                    // Use method codes and titles only.
                    $title = $code;
                    if (isset($method['title']) && ! empty($method['title'])) {
                        $title = Mage::helper('payment')->__($method['title']) . " ($code)";
                    }

                    // For simple display.
                    $methods[$code] = $title;

                    // For grouped display.
                    $item = array('value' => $code, 'label' => $title);

                    if (isset($method['group'])) {
                        if (isset($groupedMethods[$method['group']])) {
                            $groupedMethods[$method['group']]['value'][$code] = $item;
                        } else {
                            $groupedMethods[$method['group']] = array(
                                'label' => $method['group'],
                                'value' => array($code => $item)
                            );
                        }
                    } else {
                        $groupedMethods[$code] = $item;
                    }
                }

                $block->addColumnAfter(
                    'payment_method', array(
                        'header' => $this->_getHelper()->__('Payment Method'),
                        'index' => 'payment_method',
                        'type' => 'options',
                        'width' => '50px',
                        'options' => $methods,
                        'option_groups' => $groupedMethods,
                        'filter_index' => version_compare(Mage::getVersion(), '1.4.1.1', '<') ? '_table_payment_method.value' : 'payzen_payment.method'
                    ), 'status'
                );

                $block->sortColumnsByOrder();
                $this->_updateGridCollection($block);
            }

            // Case of virtual methods.
            $column = $block->getColumn('payment_method');
            $groupedMethods = $column->getData('option_groups');
            $methods = $column->getData('options');

            foreach ($availableMethods as $code => $method) {
                if (preg_match('#^payzen_multi_[1-9]\d*x$#', $code)) {
                    unset($groupedMethods[$code]);

                    $title = $availableMethods['payzen_multi']['title'] . " ($code)";
                    $groupedMethods['payzen']['value'][$code] = array('value' => $code, 'label' => $title);
                    $methods[$code] = $title;
                } elseif (preg_match('#^payzen_other_[\s\S]#', $code)) {
                    unset($groupedMethods[$code]);

                    $title = $method['title'] . " ($code)";
                    $groupedMethods['payzen']['value'][$code] = array('value' => $code, 'label' => $title);
                    $methods[$code] = $title;
                }
            }

            $column->setData('option_groups', $groupedMethods);
            $column->setData('options', $methods);
        }
    }

    protected function _updateGridCollection($block)
    {
        $collection = $block->getCollection();

        if (version_compare(Mage::getVersion(), '1.4.1.1', '<')) {
            $paymentCollection = Mage::getResourceModel('sales/order_payment_collection');
            $entityTypeId = $paymentCollection->getEntity()->getTypeId();
            $methodAttrId = $paymentCollection->getEntity()->getAttribute('method')->getAttributeId();

            $collection->getSelect()
                ->joinLeft(
                    array('_table_payment' => $collection->getTable('sales_order_entity')),
                    '`_table_payment`.`parent_id` = `e`.`entity_id` AND `_table_payment`.`entity_type_id` = ' . $entityTypeId,
                    array()
                )

                ->joinLeft(
                    array('_table_payment_method' => $collection->getTable('sales_order_entity_varchar')),
                    '(`_table_payment_method`.`entity_id` = `_table_payment`.`entity_id` AND `_table_payment_method`.`attribute_id` = ' . $methodAttrId . ')',
                    array('payment_method' => 'value')
                );
        } else {
            $paymentTable = $collection->getTable('sales/order_payment');

            $collection->getSelect()->joinLeft(
                array('payzen_payment' => $paymentTable),
                '(payzen_payment.parent_id = main_table.entity_id AND payzen_payment.entity_id = (SELECT min(p.entity_id) FROM ' . $paymentTable . ' p WHERE p.parent_id = main_table.entity_id))',
                array('payment_method' => 'payzen_payment.method')
            );
        }

        // Clear collection.
        $collection->clear();

        $this->_addPaymentMethodFilter($block);
        $this->_addPaymentMethodOrder($block);

        // Reload collection.
        $collection->load();
    }

    protected function _addPaymentMethodFilter($block)
    {
        $data = $block->getParam($block->getVarNameFilter(), null); // Load filter params from request.

        if (is_string($data)) {
            $data = Mage::helper('adminhtml')->prepareFilterString($data);
        }

        $column = $block->getColumn('payment_method');

        if (is_array($data) && isset($data['payment_method']) && strlen($data['payment_method']) > 0 && $column->getFilter()) {
            $column->getFilter()->setValue($data['payment_method']);

            $field = ($column->getFilterIndex()) ? $column->getFilterIndex() : $column->getIndex();
            $cond = $column->getFilter()->getCondition();
            if ($field && isset($cond)) {
                try {
                    $block->getCollection()->addFieldToFilter($field, $cond);
                } catch (Exception $e) {
                    $sql = $block->getCollection()->getConnection()->quoteInto("$field = ?", $cond['eq']);
                    $block->getCollection()->getSelect()->where($sql);
                }
            }
        }
    }

    protected function _addPaymentMethodOrder($block)
    {
        $columnId = $block->getParam($block->getVarNameSort(), null); // Load sort column from request.

        if ($columnId === 'payment_method') { // Only override if sort column is ours.
            $dir = $block->getParam($block->getVarNameDir(), null); // Load sort dir from request.
            $dir = (strtoupper($dir) === 'DESC') ? 'DESC' : 'ASC';

            $column = $block->getColumn('payment_method');

            $column->setDir($dir);
            $field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();

            $block->getCollection()->getSelect()->order($field . ' ' . $column->getDir());
        }
    }

    public function doOneclickQuoteProcess($observer)
    {
        $block = $observer->getBlock();

        if ($block->getNameInLayout() === 'cart_sidebar.extra_actions' || $block->getNameInLayout() === 'checkout.cart.methods') {
            $currentQuote = Mage::getSingleton('checkout/session')->getQuote();

            if ($currentQuote && $currentQuote->getItemsCount()) {
                $this->_oneclickQuoteProcess($currentQuote);
            }
        } elseif ($block->getNameInLayout() === 'alert.urls') {
            if (($product = Mage::registry('product')) && $product->getId()) {
                $this->_oneclickQuoteProcess($product);
            }
        }
    }

    protected function _oneclickQuoteProcess($data)
    {
        if (! Mage::getModel('payzen/payment_standard')->isOneclickAvailable()) {
            // No 1-Click payment.
            return;
        }

        $session = Mage::getSingleton('payzen/session');
        $quote = $session->getQuote();

        // Remove all 1-Click quote items to refresh it.
        foreach ($quote->getItemsCollection() as $item) {
            $quote->removeItem($item->getId());
        }

        $quote->getShippingAddress()->removeAllShippingRates();
        $quote->setCouponCode('');

        // Fill with current viewed element.
        if ($data instanceof Mage_Catalog_Model_Product) {
            try {
                $result = $quote->addProduct($data);

                if (is_string($result)) {
                    $this->_getHelper()->log('Product view: ' . $result);
                }
            } catch (Exception $e) {
                $this->_getHelper()->log('Product view: ' . $e->getMessage());
            }
        } elseif ($data instanceof Mage_Sales_Model_Quote) {
            foreach ($data->getItemsCollection() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }

                $product = $item->getProduct();

                // Retrieve all item options.
                $option = $item->getOptionByCode('info_buyRequest');
                $request = new Varien_Object(
                    $option ? unserialize($option->getValue()) : array('product_id' => $product->getId())
                );
                $request->setQty($item->getQty());

                try {
                    $quote->addProduct($product, $request);
                } catch (Exception $e) {
                    $this->_getHelper()->log('Cart view: ' . $e->getMessage());
                }
            }

            // Set coupon code if any.
            $quote->setCouponCode($data->getCouponCode());
        }

        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals()->save();

        $session->unsetQuote();
        $session->setQuoteId($quote->getId());
    }

    public function doOneclickUnsetQuote($observer)
    {
        $session = Mage::getSingleton('payzen/session');

        $session->getQuote()->delete();
        $session->unsetAll();
    }

    public function doPaymentButtonsManage($observer)
    {
        $block = $observer->getBlock();

        if (isset($block) && $block->getModuleName() === 'Mage_Adminhtml' && $block->getId() === 'sales_order_view') {
            $order = $block->getOrder();

            if ($order && $order->getPayment() && stripos($order->getPayment()->getMethod(), 'payzen_') === 0) {
                switch ($order->getStatus()) {
                    case 'payzen_to_validate':
                        $message = $this->_getHelper()->__('Are you sure you want to validate this order in PayZen gateway?');

                        $block->addButton(
                            'payzen_validate_payment', array(
                                'label'     => $this->_getHelper()->__('Validate payment'),
                                'onclick'   => "confirmSetLocation('{$message}', '{$block->getUrl('adminhtml/payzen_payment/validate')}')",
                                'class'     => 'go'
                            )
                        );

                        // Break omitted intentionally.

                    case 'payment_review':
                        $block->removeButton('accept_payment');
                        break;

                    default:
                        break;
                }
            }
        }
    }

    public function doAfterPaymentSectionEdit($observer)
    {
        if (Mage::app()->getRequest()->getParam('section') !== 'payment') {
            return;
        }

        // Response content.
        $output = Mage::app()->getLayout()->getOutput();

        $preferedMaxInputVars = 0;
        $preferedMaxInputVars += substr_count($output, 'name="groups[');
        $preferedMaxInputVars += substr_count($output, 'name="config_state[');
        $preferedMaxInputVars += 100; // To take account of dynamically created inputs.

        $block = Mage::app()->getLayout()->getMessagesBlock();
        if ((ini_get('suhosin.post.max_vars') && ini_get('suhosin.post.max_vars') < $preferedMaxInputVars)
            || (ini_get('suhosin.request.max_vars') && ini_get('suhosin.request.max_vars') < $preferedMaxInputVars)
        ) {
            $block->addWarning($this->_getHelper()->__('Warning, please increase the suhosin patch for PHP post and request limits to save module configurations correctly. Recommended value is %s.', $preferedMaxInputVars));
        } elseif (ini_get('max_input_vars') && ini_get('max_input_vars') < $preferedMaxInputVars) {
            $block->addWarning($this->_getHelper()->__('Warning, please increase the value of the max_input_vars directive in php.ini to save module configurations correctly. Recommended value is %s.', $preferedMaxInputVars));
        }
    }

    public function doReplacePrototypeLibrary()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        // Do nothing if standard payment is not enabled.
        if (! Mage::getModel('payzen/payment_standard')->isAvailable($quote)){
            return $this;
        }

        // Do nothing if payment by embedded fields is not enabled.
        if (! Mage::getModel('payzen/payment_standard')->isEmbedded()) {
            return $this;
        }

        // Get head block.
        $head = Mage::app()->getLayout()->getBlock('head');

        // Do nothing if head block doesn't exist.
        if (! $head) {
            return $this;
        }

        // Get head items
        $headItems = $head->getData('items');

        // Replace library.
        if (isset($headItems['js/prototype/prototype.js'])) {
            $headItems['js/prototype/prototype.js']['name'] = 'payzen/prototype/prototype.js';
            $head->setData('items', $headItems);
        }

        return $this;
    }

    /**
     * Return payzen data helper.
     *
     * @return Lyranetwork_Payzen_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('payzen');
    }
}
