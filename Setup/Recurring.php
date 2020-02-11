<?php

namespace Dotdigitalgroup\Email\Setup;

use Dotdigitalgroup\Email\Model\Sync\IntegrationInsightsFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ExternalFKSetup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Dotdigitalgroup\Email\Setup\Schema\Shared;

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
     * @var Shared
     */
    private $shared;

    /**
     * @var IntegrationInsightsFactory
     */
    private $integrationInsightsFactory;

    /**
     * @param ExternalFKSetup $externalFKSetup
     * @param Shared $shared
     * @param IntegrationInsightsFactory $integrationInsightsFactory
     */
    public function __construct(
        ExternalFKSetup $externalFKSetup,
        Shared $shared,
        IntegrationInsightsFactory $integrationInsightsFactory
    ) {
        $this->shared = $shared;
        $this->externalFKSetup = $externalFKSetup;
        $this->integrationInsightsFactory = $integrationInsightsFactory;
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
        $this->checkAndCreateAbandonedCart($setup, $context);

        $setup->endSetup();

        $this->syncIntegrationData();
    }

    /**
     * Sync integration data with Engagement Cloud
     */
    private function syncIntegrationData()
    {
        try {
            $this->integrationInsightsFactory->create()->sync();
        } catch (LocalizedException $e) {

        }
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
        $abandonedCartTableName = $setup->getTable(SchemaInterface::EMAIL_ABANDONED_CART_TABLE);

        if (version_compare($context->getVersion(), '2.3.8', '>') &&
            ! $connection->isTableExists($abandonedCartTableName)
        ) {
            $this->shared->createAbandonedCartTable($setup, $abandonedCartTableName);
        }
    }
}
