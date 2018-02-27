<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\Setup\ExternalFKSetup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Catalog recurring setup
 */
class Recurring implements InstallSchemaInterface
{
    /**
     * @var ExternalFKSetup
     */
    protected $externalFKSetup;

    /**
     * @param ExternalFKSetup $externalFKSetup
     */
    public function __construct(
        ExternalFKSetup $externalFKSetup
    ) {
        $this->externalFKSetup = $externalFKSetup;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $this->externalFKSetup->install(
            $installer,
            'catalog_product_entity',
            'entity_id',
            'email_catalog',
            'product_id'
        );

        $this->checkAndCreateAbandonedCart($setup, $context);

        $installer->endSetup();
    }

    /**
     * Create table for abandoned carts if doesn't exists between two versions.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    private function checkAndCreateAbandonedCart($setup, $context)
    {
        $connection = $setup->getConnection();
        $abandonedCartTableName = $setup->getTable('email_abandoned_cart');

        if (version_compare($context->getVersion(), '2.3.8', '>') &&
            ! $connection->isTableExists($abandonedCartTableName)
        ) {
            $abandonedCartTable = $connection->newTable($abandonedCartTableName);
            $abandonedCartTable = $this->addColumnForAbandonedCartTable($abandonedCartTable);
            $abandonedCartTable = $this->addIndexKeyForAbandonedCarts($setup, $abandonedCartTable);
            $abandonedCartTable->setComment('Abandoned Carts Table');
            $setup->getConnection()->createTable($abandonedCartTable);
        }
    }

    /**
     * @param Table $abandonedCartTable
     * @return mixed
     */
    private function addColumnForAbandonedCartTable($abandonedCartTable)
    {
        return $abandonedCartTable->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ],
            'Primary Key'
        )
            ->addColumn(
                'quote_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Quote Id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                10,
                ['unsigned' => true, 'nullable' => true],
                'Store Id'
            )
            ->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
                'Customer ID'
            )
            ->addColumn(
                'email',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Email'
            )
            ->addColumn(
                'is_active',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                'Quote Active'
            )
            ->addColumn(
                'quote_updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'Quote updated at'
            )
            ->addColumn(
                'abandoned_cart_number',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Abandoned Cart number'
            )
            ->addColumn(
                'items_count',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => 0],
                'Quote items count'
            )
            ->addColumn(
                'items_ids',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => true],
                'Quote item ids'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'Updated at'
            );
    }

    /**
     * @param SchemaSetupInterface $installer
     * @param Table $abandonedCartTable
     * @return mixed
     */
    private function addIndexKeyForAbandonedCarts($installer, $abandonedCartTable)
    {
        return $abandonedCartTable->addIndex(
            $installer->getIdxName('email_abandoned_cart', ['quote_id']),
            ['quote_id']
        )
            ->addIndex(
                $installer->getIdxName('email_abandoned_cart', ['store_id']),
                ['store_id']
            )
            ->addIndex(
                $installer->getIdxName('email_abandoned_cart', ['customer_id']),
                ['customer_id']
            )
            ->addIndex(
                $installer->getIdxName('email_abandoned_cart', ['email']),
                ['email']
            );
    }
}
