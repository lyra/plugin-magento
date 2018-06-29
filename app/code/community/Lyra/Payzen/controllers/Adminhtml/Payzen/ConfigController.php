<?php
/**
 * PayZen V2-Payment Module version 1.9.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
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

/**
 * PayZen Admin Configuraion Controller
 */
class Lyra_Payzen_Adminhtml_Payzen_ConfigController extends Mage_Adminhtml_Controller_Action
{
    public function resetAction()
    {
        $resource = Mage::getSingleton('core/resource');

        // retrieve write connection
        $writeConnection = $resource->getConnection('core_write');

        // get sales_flat_order table name & execute update query
        $table = $resource->getTableName('sales/order');
        $query = "UPDATE `{$table}` SET status = 'pending_payment' WHERE status = 'pending_vads'
                  OR status = 'pending_vadsmulti' OR status = 'pending_payzen'
                  OR status = 'pending_payzenmulti' OR status = 'pending_pwbpv1'" ;
        $writeConnection->query($query);

        if (version_compare(Mage::getVersion(), '1.4.1.1', '<')) {
            // no "sales/order_payment" table in versions < 1.4.1.1, data are saved in sales_order_entity_varchar table
            $table = $resource->getTableName('sales_order_entity_varchar');
            $query = "UPDATE `{$table}` SET value = 'payzen_standard' WHERE value = 'vads' OR value = 'payzen'";
            $writeConnection->query($query);

            $query = "UPDATE `{$table}` SET value = 'payzen_multi' WHERE value = 'vadsmulti' OR value = 'payzenmulti'";
            $writeConnection->query($query);

            $query = "UPDATE `{$table}` SET value = 'systempay_standard' WHERE value = 'pwbpv1'";
            $writeConnection->query($query);

            $query = "UPDATE `{$table}` SET value = 'pending_payment' WHERE value = 'pending_vads'
                      OR value = 'pending_vadsmulti' OR value = 'pending_payzen'
                      OR value = 'pending_payzenmulti' OR value = 'pending_pwbpv1'" ;
            $writeConnection->query($query);
        } else {
            // get sales_flat_order_payment table name & execute update query
            $table = $resource->getTableName('sales/order_payment');

            // FacilyPay Oney case
            $query = "UPDATE `{$table}` SET method = 'payzen_oney' WHERE cc_type LIKE 'ONEY%'
                      AND (method = 'vads' OR method = 'payzen')";
            $writeConnection->query($query);

            $query = "UPDATE `{$table}` SET method = 'payzen_standard' WHERE method = 'vads' OR method = 'payzen'";
            $writeConnection->query($query);

            $query = "UPDATE `{$table}` SET method = 'payzen_multi' WHERE method = 'vadsmulti'
                      OR method = 'payzenmulti'";
            $writeConnection->query($query);

            $query = "UPDATE `{$table}` SET method = 'systempay_standard' WHERE method = 'pwbpv1'";
            $writeConnection->query($query);
        }

        // get sales_flat_quote_payment table name & execute update query
        $table = $resource->getTableName('sales/quote_payment');
        $query = "UPDATE `{$table}` SET method = 'payzen_standard' WHERE method = 'vads' OR method = 'payzen'";
        $writeConnection->query($query);

        $query = "UPDATE `{$table}` SET method = 'payzen_multi' WHERE method = 'vadsmulti' OR method = 'payzenmulti'";
        $writeConnection->query($query);

        $query = "UPDATE `{$table}` SET method = 'systempay_standard' WHERE method = 'pwbpv1'";
        $writeConnection->query($query);

        // get config data model table name & execute query
        $table = $resource->getTableName('core/config_data');
        $query = "DELETE FROM `{$table}`
                         WHERE (`path` LIKE 'payment/payzen%' AND `path` NOT LIKE 'payment/payzen_multi_%x/model')
                         OR `path` LIKE 'payment/vads%'";
        $writeConnection->query($query);

        // clear cache
        Mage::getConfig()->removeCache();

        $session = Mage::getSingleton('adminhtml/session');
        $session->addSuccess(Mage::helper('payzen')->__('The configuration of the PayZen module has been successfully reset.'));

        // redirect to payment config editor
        $this->_redirect('adminhtml/system_config/edit', array('_secure' => true, 'section' => 'payment'));
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config');
    }
}
