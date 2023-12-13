<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\Setup\ExternalFKSetup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Catalog recurring setup
 */
class Recurring implements InstallSchemaInterface
{
    /**
     * @var ExternalFKSetup
     */
    private $externalFKSetup;

    /**
     * Recurring constructor.
     *
     * @param ExternalFKSetup $externalFKSetup
     */
    public function __construct(
        ExternalFKSetup $externalFKSetup
    ) {
        $this->externalFKSetup = $externalFKSetup;
    }

    /**
     * Install.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->externalFKSetup->install(
            $setup,
            'catalog_product_entity',
            'entity_id',
            SchemaInterface::EMAIL_CATALOG_TABLE,
            'product_id'
        );

        $setup->endSetup();
    }
}
