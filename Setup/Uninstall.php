<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

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

        $this->dropTable($setup, Schema::EMAIL_CONTACT_CONSENT_TABLE);
        $this->dropTable($setup, Schema::EMAIL_CONTACT_TABLE);
        $this->dropTable($setup, Schema::EMAIL_ORDER_TABLE);
        $this->dropTable($setup, Schema::EMAIL_CAMPAIGN_TABLE);
        $this->dropTable($setup, Schema::EMAIL_REVIEW_TABLE);
        $this->dropTable($setup, Schema::EMAIL_WISHLIST_TABLE);
        $this->dropTable($setup, Schema::EMAIL_CATALOG_TABLE);
        $this->dropTable($setup, Schema::EMAIL_RULES_TABLE);
        $this->dropTable($setup, Schema::EMAIL_IMPORTER_TABLE);
        $this->dropTable($setup, Schema::EMAIL_AUTOMATION_TABLE);
        $this->dropTable($setup, Schema::EMAIL_ABANDONED_CART_TABLE);
        $this->dropTable($setup, Schema::EMAIL_FAILED_AUTH_TABLE);

        $defaultConnection->dropColumn(
            $this->getTableNameWithPrefix($setup, 'admin_user'),
            'refresh_token'
        );

        $defaultConnection->delete(
            $this->getTableNameWithPrefix($setup, 'core_config_data'),
            "path LIKE 'connector_api_credentials/%'"
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param string $tableName
     */
    private function dropTable(SchemaSetupInterface $setup, $tableName)
    {
        $connection = $setup->getConnection();
        $connection->dropTable($this->getTableNameWithPrefix($setup, $tableName));
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param string $tableName
     *
     * @return string
     */
    private function getTableNameWithPrefix(SchemaSetupInterface $setup, $tableName)
    {
        return $setup->getTable($tableName);
    }
}
