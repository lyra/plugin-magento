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

/**
 * This file is recognized by 1.6 and up Magento versions.
 */

/** @var $this Lyra_Payzen_Model_Resource_Setup */
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
    '999' // sort_order
);

// empty log file
$io = new Varien_Io_File();
$logDir = Mage::getBaseDir('var') . DS . 'log';
$logFileName = $logDir . DS . 'payzen.log';
if ($io->fileExists($logFileName)) {
    $io->open(array('path' => $logDir));
    $io->streamOpen($logFileName, 'w'); // just for emptying module log file
    $io->streamClose();
}
