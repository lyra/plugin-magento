<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Adminhtml_Payzen_PaymentController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Redirect customer to the payment gateway.
     */
    public function formAction()
    {
        $this->_getDataHelper()->log('Start =================================================');
        $this->_getPaymentHelper()->doPaymentForm($this);
        $this->_getDataHelper()->log('End =================================================');
    }

    /**
     * Action called after the client returns from payment gateway.
     */
    public function returnAction()
    {
        $this->_getDataHelper()->log('Start =================================================');
        $this->_getPaymentHelper()->doPaymentReturn($this);
        $this->_getDataHelper()->log('End =================================================');
    }

    /**
     * Action called when Validate payment button is clicked in backend order view.
     */
    public function validateAction()
    {
        $this->_getDataHelper()->log('Start =================================================');

        $this->getAdminSession()->getMessages(true);

        // Retrieve order to validate.
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);
        if (! $order->getId()) {
            $this->getAdminSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }

        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);

        $payment = $order->getPayment();
        $payment->getMethodInstance()->validatePayment($payment);
        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getId()));

        $this->_getDataHelper()->log('End =================================================');
    }

    /**
     * Redirect to checkout initial page (when payment cannot be done).
     *
     * @param string $msg
     */
    public function redirectBack($msg)
    {
        // Clear all messages from session.
        $this->getCheckout()->getMessages(true);
        $this->getAdminSession()->getMessages(true);

        $this->getAdminSession()->addError($this->__($msg));

        $this->_getDataHelper()->log($msg . ' Redirecting to create order page.');
        $this->_redirect('adminhtml/sales_order_create/index');
    }

    /**
     * Redirect to error page (when an unexpected error occurred).
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function redirectError($order)
    {
        // Clear all messages from session.
        $this->getCheckout()->getMessages(true);
        $this->getAdminSession()->getMessages(true);

        $this->getAdminSession()->addError($this->__('An error has occurred during the payment process.'));

        $this->_getDataHelper()->log("Redirecting to order create page for order #{$order->getIncrementId()}.");
        $this->_redirect('adminhtml/sales_order_create/index');
    }

    /**
     * Redirect to result page (according to payment status).
     *
     * @param Mage_Sales_Model_Order $order
     * @param $case
     * @param $checkUrlWarn
     */
    public function redirectResponse($order, $case, $checkUrlWarn = false)
    {
        // Clear all messages in session.
        $this->getCheckout()->getMessages(true);
        $this->getAdminSession()->getMessages(true);

        $storeId = $order->getStore()->getId();
        if ($this->_getDataHelper()->getCommonConfigData('ctx_mode', $storeId) === 'TEST') {
            if (Lyranetwork_Payzen_Helper_Data::$pluginFeatures['prodfaq']) {
                // Display going to production message.
                $message = $this->__('<b>GOING INTO PRODUCTION:</b> You want to know how to put your shop into production mode, please read chapters &laquo; Proceeding to test phase &raquo; and &laquo; Shifting the shop to production mode &raquo; in the documentation of the module.');
                $this->getAdminSession()->addNotice($message);
            }

            if ($checkUrlWarn) {
                // Order not validated by notification URL, in TEST mode, user is webmaster.
                // So display a warning about notification URL not working.

                if ($this->_getDataHelper()->isMaintenanceMode()) {
                    $message = $this->__('The shop is in maintenance mode.The automatic notification cannot work.');
                } else {
                    $message = $this->__('The automatic validation hasn\'t worked. Have you correctly set up the notification URL in your PayZen Back Office?');
                    $message .= '<br /><br />';
                    $message .= $this->__('For understanding the problem, please read the documentation of the module:<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo; To read carefully before going further &raquo;<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo; Notification URL settings &raquo;');
                }

                $this->getAdminSession()->addError($message);
            }
        }

        if ($case === Lyranetwork_Payzen_Helper_Payment::SUCCESS) {
            $this->_getDataHelper()->log("Redirecting to order review page for order #{$order->getIncrementId()}.");
            $this->getCheckout()->setLastSuccessQuoteId($order->getQuoteId());
            $this->getAdminSession()->addSuccess(
                $this->__('The payment was successful. Your order was registered successfully.')
            );
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getIncrementId()));
        } else {
            $this->_getDataHelper()->log("Unsetting order data in session for order #{$order->getIncrementId()}.");
            $this->getCheckout()->unsLastQuoteId()
                ->unsLastSuccessQuoteId()
                ->unsLastOrderId()
                ->unsLastRealOrderId();

            $this->_getDataHelper()->log("Redirecting to order review page for order #{$order->getIncrementId()}.");

            if ($case === Lyranetwork_Payzen_Helper_Payment::FAILURE) {
                $this->getAdminSession()->addWarning($this->__('Your payment was not accepted. Please, try to re-order.'));
            }

            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getId()));
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/create');
    }

    /**
     * Get checkout session namespace.
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    public function getCheckout()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    /**
     * Get admin main session namespace.
     *
     * @return Mage_Adminhtml_Model_Session
     */
    public function getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * Return data helper.
     *
     * @return Lyranetwork_Payzen_Helper_Data
     */
    protected function _getDataHelper()
    {
        return Mage::helper('payzen');
    }

    /**
     * Return payment helper.
     *
     * @return Mage_Payzen_Helper_Payment
     */
    protected function _getPaymentHelper()
    {
        return Mage::helper('payzen/payment');
    }
}
