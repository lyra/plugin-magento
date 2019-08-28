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
 * @var $this Lyranetwork_Payzen_Model_Resource_Setup
 */
$installer = $this;

$installer->addAttribute(
    'customer',
    'payzen_sepa_identifier',
    array(
        'type' => 'varchar',
        'input' => 'text',
        'label' => 'PayZen SEPA identifier',

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
    'payzen_sepa_identifier',
    '999' // Sort order
);

$installer->addAttribute(
    'customer',
    'payzen_sepa_iban',
    array(
        'type' => 'varchar',
        'input' => 'text',
        'label' => 'PayZen masked iban',

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

$installer->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'payzen_sepa_iban',
    '999' // Sort order.
);
