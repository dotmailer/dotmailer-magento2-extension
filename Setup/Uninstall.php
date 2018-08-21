<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    /**
     * Invoked when remove-data flag is set during module uninstall.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $defaultConnection = $setup->getConnection();

        $defaultConnection->dropTable(Schema::EMAIL_CONTACT_CONSENT_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_CONTACT_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_ORDER_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_CAMPAIGN_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_REVIEW_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_WISHLIST_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_CATALOG_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_RULES_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_IMPORTER_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_AUTOMATION_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_ABANDONED_CART_TABLE);
        $defaultConnection->dropTable(Schema::EMAIL_FAILED_AUTH_TABLE);

        $defaultConnection->dropColumn(
            $defaultConnection->getTableName('admin_user'),
            'refresh_token'
        );

        $configTable = $defaultConnection->getTableName('core_config_data');
        $defaultConnection->delete($configTable, "path LIKE 'connector_api_credentials/%'");
    }
}
