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
 * This file is recognized by 1.6 and up Magento versions.
 */

/**
 * @var $this Lyra_Payzen_Model_Resource_Setup 
 */
$installer = $this;

$installer->addAttribute(
    'customer',
    'payzen_identifier',
    array(
        'type' => 'varchar',
        'input' => 'text',
        'label' => 'PayZen identifier',

        'global' => 1,
        'visible' => 0,
        'searchable' => 0,
        'filterable' => 0,
        'comparable' => 0,
        'visible_on_front' => 0,
        'required' => 0,
        'user_defined' => 0,
        'default' => '',
        'source' => null
    )
);

$entityTypeId     = $installer->getEntityTypeId('customer');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$installer->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'payzen_identifier',
    '999' // Sort_order.
);

// Empty log file.
$io = new Varien_Io_File();
$logDir = Mage::getBaseDir('var') . DS . 'log';
$logFileName = $logDir . DS . 'payzen.log';
if ($io->fileExists($logFileName)) {
    $io->open(array('path' => $logDir));
    $io->streamOpen($logFileName, 'w'); // Just for emptying module log file.
    $io->streamClose();
}
