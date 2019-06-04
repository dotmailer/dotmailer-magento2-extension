<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * @var \Dotdigitalgroup\Email\Model\Config\Json
     */
    public $json;

    /**
     * @var Schema\Shared
     */
    private $shared;

    /**
     * UpgradeSchema constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Config\Json $json
     * @param Schema\Shared $shared
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Config\Json $json,
        Schema\Shared $shared
    ) {
        $this->shared = $shared;
        $this->json = $json;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();

        $this->upgradeOneOneZeoToTwoTwoOne($setup, $context, $connection);
        $this->upgradeTwoThreeSixToTwoFiveFour($setup, $context);
        $this->upgradeTwoFiveFourToThreeZeroThree($setup, $context);
        $this->upgradeThreeTwoTwo($setup, $context);

        $setup->endSetup();
    }

    /**
     * @param AdapterInterface $connection
     * @param SchemaSetupInterface $setup
     *
     * @return void
     */
    private function upgradeTwoOSix($connection, $setup)
    {
        //modify email_campaign table
        $campaignTable = $setup->getTable(Schema::EMAIL_CAMPAIGN_TABLE);

        //add columns
        $connection->addColumn(
            $campaignTable,
            'send_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => false,
                'default' => '',
                'comment' => 'Campaign Send Id'
            ]
        );
        $connection->addColumn(
            $campaignTable,
            'send_status',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Send Status'
            ]
        );

        if ($connection->tableColumnExists($campaignTable, 'is_sent')) {
            //update table with historical send values
            $select = $connection->select();

            //join
            $select->joinLeft(
                ['oc' => $campaignTable],
                "oc.id = nc.id",
                [
                    'send_status' => new \Zend_Db_Expr(\Dotdigitalgroup\Email\Model\Campaign::SENT)
                ]
            )->where('oc.is_sent =?', 1);

            //update query from select
            $updateSql = $select->crossUpdateFromSelect(['nc' => $campaignTable]);

            //run query
            $connection->query($updateSql);

            //remove column
            $connection->dropColumn($campaignTable, 'is_sent');
        }

        //add index
        $connection->addIndex(
            $campaignTable,
            $setup->getIdxName($campaignTable, ['send_status']),
            ['send_status']
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param AdapterInterface $connection
     *
     * @return null
     */
    private function convertDataForConfig(SchemaSetupInterface $setup, $connection)
    {
        $configTable = $setup->getTable('core_config_data');
        //customer and order custom attributes from config
        $select = $connection->select()->from(
            $configTable
        )->where(
            'path IN (?)',
            [
                'connector_automation/order_status_automation/program',
                'connector_data_mapping/customer_data/custom_attributes'
            ]
        );
        $rows = $setup->getConnection()->fetchAssoc($select);

        $serializedRows = array_filter($rows, function ($row) {
            return $this->isSerialized($row['value']);
        });

        foreach ($serializedRows as $id => $serializedRow) {
            $convertedValue = $this->json->serialize($this->unserialize($serializedRow['value']));
            $bind = ['value' => $convertedValue];
            $where = [$connection->quoteIdentifier('config_id') . '=?' => $id];
            $connection->update($configTable, $bind, $where);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param AdapterInterface $connection
     *
     * @return null
     */
    private function convertDataForRules(SchemaSetupInterface $setup, $connection)
    {
        $rulesTable = $setup->getTable(Schema::EMAIL_RULES_TABLE);
        //rules data
        $select = $connection->select()->from($rulesTable);
        $rows = $setup->getConnection()->fetchAssoc($select);

        $serializedRows = array_filter($rows, function ($row) {
            return $this->isSerialized($row['conditions']);
        });

        foreach ($serializedRows as $id => $serializedRow) {
            $convertedValue = $this->json->serialize($this->unserialize($serializedRow['conditions']));
            $bind = ['conditions' => $convertedValue];
            $where = [$connection->quoteIdentifier('id') . '=?' => $id];
            $connection->update($rulesTable, $bind, $where);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param AdapterInterface $connection
     *
     * @return null
     */
    private function convertDataForImporter(SchemaSetupInterface $setup, $connection)
    {
        $importerTable = $setup->getTable(Schema::EMAIL_IMPORTER_TABLE);
        //imports that are not imported and has TD data
        $select = $connection->select()
            ->from($importerTable)
            ->where('import_status =?', 0)
            ->where('import_type IN (?)', ['Catalog_Default', 'Orders' ])
        ;
        $rows = $setup->getConnection()->fetchAssoc($select);

        $serializedRows = array_filter($rows, function ($row) {
            return $this->isSerialized($row['import_data']);
        });

        foreach ($serializedRows as $id => $serializedRow) {
            $convertedValue = $this->json->serialize($this->unserialize($serializedRow['import_data']));
            $bind = ['import_data' => $convertedValue];
            $where = [$connection->quoteIdentifier('id') . '=?' => $id];
            $connection->update($importerTable, $bind, $where);
        }
    }

    /**
     * Check if value is a serialized string
     *
     * @param string $value
     * @return boolean
     */
    private function isSerialized($value)
    {
        return (boolean) preg_match('/^((s|i|d|b|a|O|C):|N;)/', $value);
    }

    /**
     * @param string $string
     * @return mixed
     */
    private function unserialize($string)
    {
        if (false === $string || null === $string || '' === $string) {
            throw new \InvalidArgumentException('Unable to unserialize value.');
        }
        set_error_handler(
            function () {
                restore_error_handler();
                throw new \InvalidArgumentException('Unable to unserialize value, string is corrupted.');
            },
            E_NOTICE
        );
        $result = unserialize($string, ['allowed_classes' => false]);
        restore_error_handler();

        return $result;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param AdapterInterface $connection
     *
     * @return null
     */
    private function addIndexKeyForCatalog(
        SchemaSetupInterface $setup,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection
    ) {
        $connection->addForeignKey(
            $setup->getFkName(
                Schema::EMAIL_CATALOG_TABLE,
                'product_id',
                'catalog_product_entity',
                'entity_id'
            ),
            $setup->getTable(Schema::EMAIL_CATALOG_TABLE),
            'product_id',
            $setup->getTable('catalog_product_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param AdapterInterface $connection
     *
     * @return null
     */
    private function addIndexKeyForOrder(
        SchemaSetupInterface $setup,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection
    ) {
        //Only add foreign key if table exist in default connection
        if ($connection->isTableExists($setup->getTable('sales_order'))) {
            $connection->addForeignKey(
                $setup->getFkName(
                    Schema::EMAIL_ORDER_TABLE,
                    'order_id',
                    'sales_order',
                    'entity_id'
                ),
                $setup->getTable(Schema::EMAIL_ORDER_TABLE),
                'order_id',
                $setup->getTable('sales_order'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function addColumnToCouponTable(SchemaSetupInterface $setup, $connection)
    {
        $couponTable = $setup->getTable('salesrule_coupon');
        $connection->addColumn(
            $couponTable,
            'generated_by_dotmailer',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => true,
                'default' => null,
                'comment' => '1 = Generated by dotmailer'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function convertDataAndAddIndexes(SchemaSetupInterface $setup, $connection)
    {
        //modify the condition column name for the email_rules table - reserved name for mysql
        $rulesTable = $setup->getTable(Schema::EMAIL_RULES_TABLE);

        if ($connection->tableColumnExists($rulesTable, 'condition')) {
            $connection->changeColumn(
                $rulesTable,
                'condition',
                'conditions',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BLOB,
                    'nullable' => false,
                    'comment' => 'Rule Conditions'
                ]
            );
        }
        /**
         * Core config data.
         */
        $this->convertDataForConfig($setup, $connection);
        /**
         * Importer data.
         */
        $this->convertDataForImporter($setup, $connection);
        /**
         * Rules conditions.
         */
        $this->convertDataForRules($setup, $connection);
        /**
         * Index foreign key for email catalog.
         */
        $this->addIndexKeyForCatalog($setup, $connection);

        /**
         * Add index foreign key for email order.
         */
        $this->addIndexKeyForOrder($setup, $connection);
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function modifyWishlistTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $emailWishlistTable = $setup->getTable(Schema::EMAIL_WISHLIST_TABLE);

        if ($connection->tableColumnExists($emailWishlistTable, 'customer_id')) {
            $connection->changeColumn(
                $emailWishlistTable,
                'customer_id',
                'customer_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => true,
                    'comment' => 'Customer ID'
                ]
            );
        }

        $connection->addForeignKey(
            $setup->getFkName(
                Schema::EMAIL_WISHLIST_TABLE,
                'customer_id',
                'customer_entity',
                'entity_id'
            ),
            $setup->getTable(Schema::EMAIL_WISHLIST_TABLE),
            'customer_id',
            $setup->getTable('customer_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
        $connection->addForeignKey(
            $setup->getFkName(
                Schema::EMAIL_WISHLIST_TABLE,
                'wishlist_id',
                'wishlist',
                'wishlist_id'
            ),
            $setup->getTable(Schema::EMAIL_WISHLIST_TABLE),
            'wishlist_id',
            $setup->getTable('wishlist'),
            'wishlist_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function upgradeOneOneZeoToTwoTwoOne(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context,
        $connection
    ) {
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            //remove quote table
            $connection->dropTable($setup->getTable('email_quote'));
        }
        if (version_compare($context->getVersion(), '2.0.6', '<')) {
            $this->upgradeTwoOSix($connection, $setup);
        }
        if (version_compare($context->getVersion(), '2.1.0', '<')) {
            $this->addColumnToCouponTable($setup, $connection);
        }

        //replace serialize with json_encode
        if (version_compare($context->getVersion(), '2.2.1', '<')) {
            $this->convertDataAndAddIndexes($setup, $connection);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    private function upgradeTwoThreeSixToTwoFiveFour(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '2.3.6', '<')) {
            $tableName = $setup->getTable(Schema::EMAIL_ABANDONED_CART_TABLE);
            $this->shared->createAbandonedCartTable($setup, $tableName);
        }

        if (version_compare($context->getVersion(), '2.5.2', '<')) {
            $tableName = $setup->getTable(Schema::EMAIL_CONTACT_CONSENT_TABLE);
            $this->shared->createConsentTable($setup, $tableName);
        }

        if (version_compare($context->getVersion(), '2.5.3', '<')) {
            $tableName = $setup->getTable(Schema::EMAIL_FAILED_AUTH_TABLE);
            $this->shared->createFailedAuthTable($setup, $tableName);
        }

        if (version_compare($context->getVersion(), '2.5.4', '<')) {
            $this->modifyWishlistTable($setup);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    private function upgradeTwoFiveFourToThreeZeroThree(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '3.0.3', '<')) {
            $definition = [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'size' => 255,
                'nullable' => false,
                'default' => '',
                'comment' => 'Contact Status'
            ];
            $setup->getConnection()->addColumn(
                $setup->getTable(Schema::EMAIL_ABANDONED_CART_TABLE),
                'status',
                $definition
            );
        }
    }

    /**
     * Adds last_subscribed_date to email_contact
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    private function upgradeThreeTwoTwo(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '3.2.2', '<')) {
            $definition = [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                'nullable' => true,
                'comment' => 'Last subscribed date'
            ];
            $setup->getConnection()->addColumn(
                $setup->getTable(Schema::EMAIL_CONTACT_TABLE),
                'last_subscribed_at',
                $definition
            );
        }
    }
}
