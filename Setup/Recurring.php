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
     * @var Schema\Shared
     */
    private $shared;

    /**
     * @param ExternalFKSetup $externalFKSetup
     * @param Schema\Shared $shared
     */
    public function __construct(
        ExternalFKSetup $externalFKSetup,
        Schema\Shared $shared
    ) {
        $this->shared = $shared;
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
            $this->shared->createAbandonedCartTable($setup, $abandonedCartTableName);
        }
    }
}
