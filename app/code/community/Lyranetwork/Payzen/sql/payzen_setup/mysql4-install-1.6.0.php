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
 * For backward compatibility (less than 1.6 Magento versions).
 */

$io = new Varien_Io_File();
$installFile = __DIR__ . DS . 'install-1.6.0.php';
if ($io->fileExists($installFile)) {
    include_once $installFile;
}

// Install data just for versions less than 1.6.
if (version_compare(Mage::getVersion(), '1.6.0.0', '<')) {
    /**
     * @var $this Lyranetwork_Payzen_Model_Resource_Setup 
     */
    $installer = $this;
    $installer->startSetup();

    $connection = $installer->getConnection();

    $statusTable = $installer->getTable('sales_order_status');
    $stateTable = $installer->getTable('sales_order_status_state');

    if ($installer->tableExists($statusTable) && $installer->tableExists($stateTable)) {
        $select = $connection->select()->from($statusTable, 'status')->where('status = "payzen_to_validate"');
        if (! $connection->fetchOne($select)) { // Status does not exist.
            $connection->insert(
                $statusTable,
                array('status' => 'payzen_to_validate', 'label' => 'To validate payment')
            );

            $connection->insert(
                $stateTable,
                array('status' => 'payzen_to_validate', 'state' => 'payment_review', 'is_default' => 0)
            );
        }
    }

    $installer->endSetup();
}
