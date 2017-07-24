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
     * {@inheritdoc}
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

        $installer->endSetup();
    }
}
