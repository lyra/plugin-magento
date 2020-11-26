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

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
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
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        // Prepare database for install.
        $setup->startSetup();

        /**
         * Add gateway identifier attribute to the customer entity.
         */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        $customerSetup->addAttribute(
            Customer::ENTITY,
            'payzen_identifier',
            [
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
            ]
        );

        /**
         * Add new gateway statuses.
         */
        $connection = $setup->getConnection();

        $select = $connection->select()
            ->from($setup->getTable('sales_order_status'), 'COUNT(*)')
            ->where('status = ?', 'payzen_to_validate');
        $count = (int) $connection->fetchOne($select);

        if ($count == 0) {
            $connection->insert(
                $setup->getTable('sales_order_status'),
                [
                    'status' => 'payzen_to_validate',
                    'label' => 'To validate payment'
                ]
            );

            $connection->insert(
                $setup->getTable('sales_order_status_state'),
                [
                    'status' => 'payzen_to_validate',
                    'state' => 'payment_review',
                    'is_default' => 0
                ]
            );
        }

        /**
         * Empty log file.
         */
        $logFileName = BP . '/var/log/payzen.log';
        if (file_exists($logFileName)) {
            $f = fopen($logFileName, 'w'); // Just for emptying module log file.
            fclose($f);
        }

        // Prepare database after install.
        $setup->endSetup();
    }
}
