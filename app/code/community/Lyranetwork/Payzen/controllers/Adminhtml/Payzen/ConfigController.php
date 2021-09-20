<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Admin Configuraion Controller
 */
class Lyranetwork_Payzen_Adminhtml_Payzen_ConfigController extends Mage_Adminhtml_Controller_Action
{
    public function resetAction()
    {
        $resource = Mage::getSingleton('core/resource');

        // Retrieve write connection.
        $writeConnection = $resource->getConnection('core_write');

        // Get sales_flat_order table name & execute update query.
        $table = $resource->getTableName('sales/order');
        $query = "UPDATE `{$table}` SET status = 'pending_payment' WHERE status = 'pending_vads'
            OR status = 'pending_vadsmulti' OR status = 'pending_payzen'
            OR status = 'pending_payzenmulti' OR status = 'pending_pwbpv1'" ;
        $writeConnection->query($query);

        if (version_compare(Mage::getVersion(), '1.4.1.1', '<')) {
            // No "sales/order_payment" table in versions < 1.4.1.1, data are saved in sales_order_entity_varchar table
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
            // Get sales_flat_order_payment table name & execute update query.
            $table = $resource->getTableName('sales/order_payment');

            $query = "UPDATE `{$table}` SET method = 'payzen_standard' WHERE method = 'vads' OR method = 'payzen'";
            $writeConnection->query($query);

            $query = "UPDATE `{$table}` SET method = 'payzen_multi' WHERE method = 'vadsmulti'
                OR method = 'payzenmulti'";
            $writeConnection->query($query);

            $query = "UPDATE `{$table}` SET method = 'systempay_standard' WHERE method = 'pwbpv1'";
            $writeConnection->query($query);
        }

        // Get sales_flat_quote_payment table name & execute update query.
        $table = $resource->getTableName('sales/quote_payment');
        $query = "UPDATE `{$table}` SET method = 'payzen_standard' WHERE method = 'vads' OR method = 'payzen'";
        $writeConnection->query($query);

        $query = "UPDATE `{$table}` SET method = 'payzen_multi' WHERE method = 'vadsmulti' OR method = 'payzenmulti'";
        $writeConnection->query($query);

        $query = "UPDATE `{$table}` SET method = 'systempay_standard' WHERE method = 'pwbpv1'";
        $writeConnection->query($query);

        // Get config data model table name & execute query.
        $table = $resource->getTableName('core/config_data');
        $query = "DELETE FROM `{$table}`
            WHERE (`path` LIKE 'payment/payzen%' AND `path` NOT LIKE 'payment/payzen_multi_%x/model')
            OR `path` LIKE 'payment/vads%'";
        $writeConnection->query($query);

        // Clear cache.
        Mage::getConfig()->removeCache();

        $session = Mage::getSingleton('adminhtml/session');
        $session->addSuccess(Mage::helper('payzen')->__('The configuration of the PayZen module has been successfully reset.'));

        // Redirect to payment config editor.
        $this->_redirect('adminhtml/system_config/edit', array('_secure' => true, 'section' => 'payment'));
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config');
    }
}
