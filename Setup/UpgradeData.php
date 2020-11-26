<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(CustomerSetupFactory $customerSetupFactory)
    {
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        // Prepare database for install.
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.4.0', '<')) {
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
            $customerSetup->updateAttribute(
                Customer::ENTITY,
                'payzen_identifier',
                [
                    'is_visible' => 1,
                    'is_system' => 0
                ]
            );

            /**
             * Add gateway masked PAN attribute to the customer entity.
             */
            $customerSetup->addAttribute(
                Customer::ENTITY,
                'payzen_masked_pan',
                [
                    'type' => 'varchar',
                    'input' => 'text',
                    'label' => 'PayZen masked PAN',

                    'global' => 1,
                    'visible' => 1,
                    'searchable' => 0,
                    'filterable' => 0,
                    'comparable' => 0,
                    'visible_on_front' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                    'default' => '',
                    'source' => null,
                    'system' => 0
                ]
            );

            /**
             * Add new gateway statuses.
             */
            $connection = $setup->getConnection();

            // Pending status for SEPA payment.
            $select = $connection->select()
                ->from($setup->getTable('sales_order_status'), 'COUNT(*)')
                ->where('status = ?', 'payzen_pending_transfer');
            $count = (int) $connection->fetchOne($select);

            if ($count == 0) {
                $connection->insert(
                    $setup->getTable('sales_order_status'),
                    [
                        'status' => 'payzen_pending_transfer',
                        'label' => 'Pending funds transfer'
                    ]
                );

                $connection->insert(
                    $setup->getTable('sales_order_status_state'),
                    [
                        'status' => 'payzen_pending_transfer',
                        'state' => 'processing',
                        'is_default' => 0
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '2.4.1', '<')) {
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
            $customerSetup->addAttribute(
                Customer::ENTITY,
                'payzen_sepa_identifier',
                [
                    'type' => 'varchar',
                    'input' => 'text',
                    'label' => 'PayZen SEPA identifier',

                    'global' => 1,
                    'visible' => 1,
                    'searchable' => 0,
                    'filterable' => 0,
                    'comparable' => 0,
                    'visible_on_front' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                    'default' => '',
                    'source' => null,
                    'system' => 0
                ]
            );

            $customerSetup->addAttribute(
                Customer::ENTITY,
                'payzen_sepa_iban_bic',
                [
                    'type' => 'varchar',
                    'input' => 'text',
                    'label' => 'PayZen SEPA IBAN/BIC',

                    'global' => 1,
                    'visible' => 1,
                    'searchable' => 0,
                    'filterable' => 0,
                    'comparable' => 0,
                    'visible_on_front' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                    'default' => '',
                    'source' => null,
                    'system' => 0
                ]
            );
        }

        // Prepare database after install.
        $setup->endSetup();
    }
}
