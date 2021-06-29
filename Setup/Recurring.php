<?php

namespace Dotdigitalgroup\Email\Setup;

use Dotdigitalgroup\Email\Logger\Logger;
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
     * @var Logger
     */
    private $logger;

    /**
     * Recurring constructor.
     * @param ExternalFKSetup $externalFKSetup
     * @param Logger $logger
     */
    public function __construct(
        ExternalFKSetup $externalFKSetup,
        Logger $logger
    ) {
        $this->externalFKSetup = $externalFKSetup;
        $this->logger = $logger;
    }

    /**
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
