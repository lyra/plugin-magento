<?php
/**
 * PayZen V2-Payment Module version 1.9.2 for Magento 1.4-1.9. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * This file is recognized by 1.6 and up Magento versions.
 */

/**
 * @var $this Lyra_Payzen_Model_Resource_Setup 
 */
$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$statusTable = $installer->getTable('sales/order_status');
$stateTable = $installer->getTable('sales/order_status_state');

$select = $connection->select()->from($statusTable, 'status')->where('status = "payzen_to_validate"');
if (! $connection->fetchOne($select)) { // status does not exist
    $connection->insert(
        $statusTable,
        array('status' => 'payzen_to_validate', 'label' => 'To validate payment')
    );

    $connection->insert(
        $stateTable,
        array('status' => 'payzen_to_validate', 'state' => 'payment_review', 'is_default' => 0)
    );
}

$installer->endSetup();
