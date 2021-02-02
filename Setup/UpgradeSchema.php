<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Dotdigitalgroup\Email\Setup\Schema\Shared;
use Dotdigitalgroup\Email\Logger\Logger;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var SerializerInterface
     */
    public $json;

    /**
     * @var Shared
     */
    private $shared;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * UpgradeSchema constructor.
     * @param SerializerInterface $json
     * @param Shared $shared
     * @param Logger $logger
     */
    public function __construct(
        SerializerInterface $json,
        Shared $shared,
        Logger $logger
    ) {
        $this->shared = $shared;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();

        $this->upgradeOneOneZeroToTwoTwoOne($setup, $context, $connection);
        $this->upgradeTwoThreeSixToTwoFiveFour($setup, $context);
        $this->upgradeTwoFiveFourToThreeZeroThree($setup, $context);
        $this->upgradeThreeTwoTwo($setup, $context);
        $this->upgradeFourZeroOne($setup, $context);
        $this->upgradeFourThreeZero($setup, $context, $connection);
        $this->upgradeFourThreeFour($setup, $context, $connection);
        $this->upgradeFourThreeSix($setup, $connection, $context);
        $this->upgradeFourFiveTwo($setup, $connection, $context);
        $this->upgradeFourElevenZero($setup, $connection, $context);

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
    private function changeColumnAndAddIndexes(SchemaSetupInterface $setup, $connection)
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
    private function upgradeOneOneZeroToTwoTwoOne(
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
        if (version_compare($context->getVersion(), '2.2.1', '<')) {
            $this->changeColumnAndAddIndexes($setup, $connection);
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

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    private function upgradeFourZeroOne(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '4.0.1', '<')) {

            $tableName = $setup->getTable(Schema::EMAIL_COUPON_TABLE);
            $this->shared->createCouponTable($setup, $tableName);

            $catalogTable = $setup->getTable(Schema::EMAIL_CATALOG_TABLE);

            if (version_compare($context->getVersion(), '3.4.2', '>=')) {

                // restore modified and imported columns
                if (!$setup->getConnection()->tableColumnExists(
                    $catalogTable,
                    'imported'
                )) {
                    $setup->getConnection()->addColumn(
                        $catalogTable,
                        'imported',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                            'nullable' => true,
                            'unsigned' => true,
                            'comment' => 'Product imported [deprecated]'
                        ]
                    );
                }

                if (!$setup->getConnection()->tableColumnExists(
                    $catalogTable,
                    'modified'
                )) {
                    $setup->getConnection()->addColumn(
                        $catalogTable,
                        'modified',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                            'nullable' => true,
                            'unsigned' => true,
                            'comment' => 'Product modified [deprecated]'
                        ]
                    );
                }

            } else {

                // Remove indexes on 'imported' and 'modified' columns
                try {
                    $setup->getConnection()->dropIndex(
                        $catalogTable,
                        'EMAIL_CATALOG_IMPORTED'
                    );
                } catch (\Exception $e) {
                    // Not critical. Continue upgrade.
                    $this->logger->debug((string) $e);
                }

                try {
                    $setup->getConnection()->dropIndex(
                        $catalogTable,
                        'EMAIL_CATALOG_MODIFIED'
                    );
                } catch (\Exception $e) {
                    // Not critical. Continue upgrade.
                    $this->logger->debug((string) $e);
                }

                // add processed and last_imported_at columns
                if (!$setup->getConnection()->tableColumnExists(
                    $catalogTable,
                    'processed'
                )) {
                    $setup->getConnection()->addColumn(
                        $catalogTable,
                        'processed',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                            'nullable' => false,
                            'unsigned' => true,
                            'comment' => 'Product processed'
                        ]
                    );

                    $setup->getConnection()->addIndex(
                        $catalogTable,
                        $setup->getIdxName($catalogTable, ['processed']),
                        ['processed']
                    );
                }

                if (!$setup->getConnection()->tableColumnExists(
                    $catalogTable,
                    'last_imported_at'
                )) {
                    $setup->getConnection()->addColumn(
                        $catalogTable,
                        'last_imported_at',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                            'nullable' => true,
                            'comment' => 'Last imported date'
                        ]
                    );

                    $setup->getConnection()->addIndex(
                        $catalogTable,
                        $setup->getIdxName($catalogTable, ['last_imported_at']),
                        ['last_imported_at']
                    );
                }
            }
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @param AdapterInterface $connection
     */
    private function upgradeFourThreeZero(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context,
        AdapterInterface $connection
    ) {
        if (version_compare($context->getVersion(), '4.3.0', '<')) {
            $tableName = $setup->getTable(Schema::EMAIL_COUPON_TABLE);
            if (!$connection->isTableExists($tableName)) {
                $this->shared->createCouponTable($setup, $tableName);
            }
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @param AdapterInterface $connection
     */
    private function upgradeFourThreeFour(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context,
        AdapterInterface $connection
    ) {
        if (version_compare($context->getVersion(), '4.3.4', '<')) {
            $couponTable = $setup->getTable(Schema::EMAIL_COUPON_TABLE);
            if (!$connection->tableColumnExists($couponTable, 'expires_at')) {
                $setup->getConnection()->addColumn($couponTable, 'expires_at', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    'nullable' => true,
                    'comment' => 'Coupon expiration date',
                ]);
            }
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param AdapterInterface $connection
     * @param ModuleContextInterface $context
     */
    private function upgradeFourThreeSix(
        SchemaSetupInterface $setup,
        AdapterInterface $connection,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '4.3.6', '<')) {

            /* Update coupon_id column name */
            $couponTable = $setup->getTable(Schema::EMAIL_COUPON_TABLE);
            if (!$connection->tableColumnExists($couponTable, 'salesrule_coupon_id')) {
                $setup->getConnection()->changeColumn($couponTable, 'coupon_id', 'salesrule_coupon_id', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => false,
                    'unsigned' => true,
                    'comment' => 'Coupon ID',
                ]);
            }

            /* Change nullable columns to 1/0 */
            $emailWishlistTable = $setup->getTable(Schema::EMAIL_WISHLIST_TABLE);
            $emailOrderTable = $setup->getTable(Schema::EMAIL_ORDER_TABLE);
            $emailContactTable = $setup->getTable(Schema::EMAIL_CONTACT_TABLE);
            $emailReviewTable = $setup->getTable(Schema::EMAIL_REVIEW_TABLE);

            if ($connection->tableColumnExists($emailWishlistTable, 'wishlist_imported')) {
                $connection->modifyColumn(
                    $emailWishlistTable,
                    'wishlist_imported',
                    [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Wishlist Imported'
                    ]
                );
            }

            if ($connection->tableColumnExists($emailOrderTable, 'email_imported')) {
                $connection->modifyColumn(
                    $emailOrderTable,
                    'email_imported',
                    [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Is Order Imported'
                    ]
                );
            }

            if ($connection->tableColumnExists($emailContactTable, 'email_imported')) {
                $connection->modifyColumn(
                    $emailContactTable,
                    'email_imported',
                    [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Is Imported'
                    ]
                );
            }

            if ($connection->tableColumnExists($emailContactTable, 'subscriber_imported')) {
                $connection->modifyColumn(
                    $emailContactTable,
                    'subscriber_imported',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'unsigned' => true,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Is Subscriber Imported'
                    ]
                );
            }

            if ($connection->tableColumnExists($emailReviewTable, 'review_imported')) {
                $connection->modifyColumn(
                    $emailReviewTable,
                    'review_imported',
                    [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Review Imported'
                    ]
                );
            }
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param AdapterInterface $connection
     * @param ModuleContextInterface $context
     */
    private function upgradeFourFiveTwo(
        SchemaSetupInterface $setup,
        AdapterInterface $connection,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '4.5.2', '<')) {
            $emailCatalogTable = $setup->getTable(Schema::EMAIL_CATALOG_TABLE);

            if ($connection->tableColumnExists($emailCatalogTable, 'imported')) {
                $connection->dropColumn($emailCatalogTable, 'imported');
            }

            if ($connection->tableColumnExists($emailCatalogTable, 'modified')) {
                $connection->dropColumn($emailCatalogTable, 'modified');
            }
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param AdapterInterface $connection
     * @param ModuleContextInterface $context
     */
    private function upgradeFourElevenZero(
        SchemaSetupInterface $setup,
        AdapterInterface $connection,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '4.11.0', '<')) {
            $emailWishlistTable = $setup->getTable(Schema::EMAIL_WISHLIST_TABLE);

            if ($connection->tableColumnExists($emailWishlistTable, 'wishlist_modified')) {
                $connection->modifyColumn(
                    $emailWishlistTable,
                    'wishlist_modified',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'Wishlist Modified [deprecated]'
                    ]
                );
            }
        }
    }
}
