<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\App\ObjectManager;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        $this->createContactTable($installer);
        $this->createOrderTable($installer);
        $this->createCampaignTable($installer);
        $this->createReviewTable($installer);
        $this->createWishlistTable($installer);
        $this->createQuoteTable($installer);
        $this->createCatalogTable($installer);
        $this->createRuleTable($installer);
        $this->createImporterTable($installer);
        $this->createAutomationTable($installer);
        $this->populateEmailContactTable($installer);
        $this->updateContactsWithCustomersThatAreSubscribers($installer);
        $this->populateEmailOrderTable($installer);
        $this->populateEmailReviewTable($installer);
        $this->populateEmailWishlistTable($installer);
        $this->populateEmailQuoteTable($installer);
        $this->populateEmailCatalogTable($installer);
        $this->addColumnToAdminUserTable($installer);

        $configModel = $this->saveValuesToConfig();
        $this->saveAllOrderStatusesAsString($configModel);
        $this->saveAllProductTypesAsString($configModel);
        $this->saveAllProductVisibilitiesAsString($configModel);

        $installer->endSetup();
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    private function createContactTable($installer)
    {
        $this->dropContactTableIfExists($installer);

        $contactTable = $installer->getConnection()->newTable(
            $installer->getTable('email_contact')
        );

        $contactTable = $this->addColumnsToContactTable($contactTable);
        $contactTable = $this->addIndexesToContactTable($installer, $contactTable);

        $contactTable->addForeignKey(
            $installer->getFkName(
                'email_contact', 'website_id', 'store_website', 'website_id'
            ),
            'website_id',
            $installer->getTable('store_website'),
            'website_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );

        $contactTable->setComment('Connector Contacts');
        $installer->getConnection()->createTable($contactTable);
    }

    /**
     * @param $installer
     */
    private function dropContactTableIfExists($installer)
    {
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_contact')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_contact')
            );
        }
    }

    /**
     * @param $contactTable
     * @return \Magento\Framework\DB\Ddl\Table
     */
    private function addColumnsToContactTable($contactTable)
    {
        return $contactTable->addColumn(
            'email_contact_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ], 'Primary Key'
            )
            ->addColumn(
                'is_guest', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null, ['unsigned' => true, 'nullable' => true], 'Is Guest'
            )
            ->addColumn(
                'contact_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 15,
                ['unsigned' => true, 'nullable' => true], 'Connector Contact ID'
            )
            ->addColumn(
                'customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                11, ['unsigned' => true, 'nullable' => false], 'Customer ID'
            )
            ->addColumn(
                'website_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Website ID'
            )
            ->addColumn(
                'store_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )
            ->addColumn(
                'email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => ''], 'Customer Email'
            )
            ->addColumn(
                'is_subscriber', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null, ['unsigned' => true, 'nullable' => true], 'Is Subscriber'
            )
            ->addColumn(
                'subscriber_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['unsigned' => true, 'nullable' => true], 'Subscriber status'
            )
            ->addColumn(
                'email_imported',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['unsigned' => true, 'nullable' => true], 'Is Imported'
            )
            ->addColumn(
                'subscriber_imported',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['unsigned' => true, 'nullable' => true], 'Subscriber Imported'
            )
            ->addColumn(
                'suppressed', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null, ['unsigned' => true, 'nullable' => true], 'Is Suppressed'
            );
    }

    /**
     * @param $installer
     * @param $contactTable
     * @return \Magento\Framework\DB\Ddl\Table
     */
    private function addIndexesToContactTable($installer, $contactTable)
    {
        return $contactTable->addIndex(
            $installer->getIdxName('email_contact', ['email_contact_id']),
            ['email_contact_id']
            )
            ->addIndex(
                $installer->getIdxName('email_contact', ['is_guest']),
                ['is_guest']
            )
            ->addIndex(
                $installer->getIdxName('email_contact', ['customer_id']),
                ['customer_id']
            )
            ->addIndex(
                $installer->getIdxName('email_contact', ['website_id']),
                ['website_id']
            )
            ->addIndex(
                $installer->getIdxName('email_contact', ['is_subscriber']),
                ['is_subscriber']
            )
            ->addIndex(
                $installer->getIdxName('email_contact', ['subscriber_status']),
                ['subscriber_status']
            )
            ->addIndex(
                $installer->getIdxName('email_contact', ['email_imported']),
                ['email_imported']
            )
            ->addIndex(
                $installer->getIdxName(
                    'email_contact', ['subscriber_imported']
                ), ['subscriber_imported']
            )
            ->addIndex(
                $installer->getIdxName('email_contact', ['suppressed']),
                ['suppressed']
            )
            ->addIndex(
                $installer->getIdxName('email_contact', ['email']),
                ['email']
            )
            ->addIndex(
                $installer->getIdxName('email_contact', ['contact_id']),
                ['contact_id']
            );
    }

    /**
     * @param $installer
     */
    private function createOrderTable($installer)
    {
        $this->dropOrderTableIfExists($installer);

        $orderTable = $installer->getConnection()->newTable(
            $installer->getTable('email_order'));

        $orderTable = $this->addColumnsToOrderTable($orderTable);
        $orderTable = $this->addIndexesToOrderTable($installer, $orderTable);

        $orderTable->addForeignKey(
            $installer->getFkName(
                $installer->getTable('email_order'), 'store_id',
                'core/store', 'store_id'
            ),
            'store_id',
            $installer->getTable('store'), 'store_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE,
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );

        $orderTable->setComment('Transactional Order Data');
        $installer->getConnection()->createTable($orderTable);
    }

    /**
     * @param $installer
     */
    private function dropOrderTableIfExists($installer)
    {
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_order')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_order')
            );
        }
    }

    /**
     * @param $orderTable
     * @return mixed
     */
    private function addColumnsToOrderTable($orderTable)
    {
        return $orderTable->addColumn(
            'email_order_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ], 'Primary Key'
            )
            ->addColumn(
                'order_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
                ['unsigned' => true, 'nullable' => false], 'Order ID'
            )
            ->addColumn(
                'order_status', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['unsigned' => true, 'nullable' => false], 'Order Status'
            )
            ->addColumn(
                'quote_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
                ['unsigned' => true, 'nullable' => false], 'Sales Quote ID'
            )
            ->addColumn(
                'store_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )
            ->addColumn(
                'email_imported',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['unsigned' => true, 'nullable' => true], 'Is Order Imported'
            )
            ->addColumn(
                'modified', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null, ['unsigned' => true, 'nullable' => true],
                'Is Order Modified'
            )
            ->addColumn(
                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Creation Time'
            )
            ->addColumn(
                'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Update Time'
            );
    }

    /**
     * @param $installer
     * @param $orderTable
     * @return mixed
     */
    private function addIndexesToOrderTable($installer, $orderTable)
    {
        return $orderTable->addIndex(
            $installer->getIdxName(
                $installer->getTable('email_order'), ['store_id']
            ), ['store_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_order'), ['quote_id']
                ), ['quote_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_order'), ['email_imported']
                ), ['email_imported']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_order'), ['order_status']
                ), ['order_status']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_order'), ['modified']
                ), ['modified']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_order'), ['updated_at']
                ), ['updated_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_order'), ['created_at']
                ), ['created_at']
            );
    }

    /**
     * @param $installer
     */
    private function createCampaignTable($installer)
    {
        $this->dropCampaignTableIfExists($installer);

        $campaignTable = $installer->getConnection()->newTable(
            $installer->getTable('email_campaign'));

        $campaignTable = $this->addColumnsToCampaignTable($campaignTable);
        $campaignTable = $this->addIndexesToCampaignTable($installer, $campaignTable);

        $campaignTable->addForeignKey(
            $installer->getFkName(
                $installer->getTable('email_campaign'), 'store_id',
                'core/store', 'store_id'
            ),
            'store_id',
            $installer->getTable('store'), 'store_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE,
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );

        $campaignTable->setComment('Connector Campaigns');
        $installer->getConnection()->createTable($campaignTable);
    }

    /**
     * @param $installer
     */
    private function dropCampaignTableIfExists($installer)
    {
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_campaign')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_campaign')
            );
        }
    }

    /**
     * @param $campaignTable
     * @return mixed
     */
    private function addColumnsToCampaignTable($campaignTable)
    {
        return $campaignTable->addColumn(
            'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ], 'Primary Key'
            )
            ->addColumn(
                'campaign_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null, ['unsigned' => true, 'nullable' => false], 'Campaign ID'
            )
            ->addColumn(
                'email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => ''], 'Contact Email'
            )
            ->addColumn(
                'customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                11, ['unsigned' => true, 'nullable' => false], 'Customer ID'
            )
            ->addColumn(
                'sent_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Send Date'
            )
            ->addColumn(
                'order_increment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 50,
                ['unsigned' => true, 'nullable' => false], 'Order Increment ID'
            )
            ->addColumn(
                'quote_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10,
                ['unsigned' => true, 'nullable' => false], 'Sales Quote ID'
            )
            ->addColumn(
                'message', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => ''], 'Error Message'
            )
            ->addColumn(
                'checkout_method', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255, ['nullable' => false, 'default' => ''],
                'Checkout Method Used'
            )
            ->addColumn(
                'store_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )
            ->addColumn(
                'event_name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => ''], 'Event Name'
            )
            ->addColumn(
                'send_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => ''], 'Send Id'
            )
            ->addColumn(
                'send_status', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0], 'Campaign send status'
            )
            ->addColumn(
                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Creation Time'
            )
            ->addColumn(
                'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Update Time'
            );
    }

    /**
     * @param $installer
     * @param $campaignTable
     * @return mixed
     */
    private function addIndexesToCampaignTable($installer, $campaignTable)
    {
        return $campaignTable->addIndex(
            $installer->getIdxName(
                $installer->getTable('email_campaign'), ['store_id']
            ), ['store_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['campaign_id']
                ), ['campaign_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['email']
                ), ['email']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['send_id']
                ), ['send_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['send_status']
                ), ['send_status']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['created_at']
                ), ['created_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['updated_at']
                ), ['updated_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['sent_at']
                ), ['sent_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['event_name']
                ), ['event_name']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['message']
                ), ['message']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['quote_id']
                ), ['quote_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_campaign'), ['customer_id']
                ), ['customer_id']
            );
    }

    /**
     * @param $installer
     */
    private function createReviewTable($installer)
    {
        $this->dropReviewTableIfExists($installer);

        $reviewTable = $installer->getConnection()->newTable(
            $installer->getTable('email_review'));

        $reviewTable = $this->addColumnsToReviewTable($reviewTable);
        $reviewTable = $this->addIndexesToReviewTable($installer, $reviewTable);

        $reviewTable->setComment('Connector Reviews');
        $installer->getConnection()->createTable($reviewTable);
    }

    /**
     * @param $installer
     */
    private function dropReviewTableIfExists($installer)
    {
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_review')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_review')
            );
        }
    }

    /**
     * @param $reviewTable
     * @return mixed
     */
    private function addColumnsToReviewTable($reviewTable)
    {
        return $reviewTable->addColumn(
            'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ], 'Primary Key'
            )
            ->addColumn(
                'review_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null, ['unsigned' => true, 'nullable' => false], 'Review Id'
            )
            ->addColumn(
                'customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                11, ['unsigned' => true, 'nullable' => false], 'Customer ID'
            )
            ->addColumn(
                'store_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5,
                ['unsigned' => true, 'nullable' => false], 'Store Id'
            )
            ->addColumn(
                'review_imported',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['unsigned' => true, 'nullable' => true], 'Review Imported'
            )
            ->addColumn(
                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Creation Time'
            )
            ->addColumn(
                'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Update Time'
            );
    }

    /**
     * @param $installer
     * @param $reviewTable
     * @return mixed
     */
    private function addIndexesToReviewTable($installer, $reviewTable)
    {
        return $reviewTable->addIndex(
            $installer->getIdxName(
                $installer->getTable('email_review'), ['review_id']
            ), ['review_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_review'), ['customer_id']
                ), ['customer_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_review'), ['store_id']
                ), ['store_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_review'), ['review_imported']
                ), ['review_imported']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_review'), ['created_at']
                ), ['created_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_review'), ['updated_at']
                ), ['updated_at']
            );
    }

    /**
     * @param $installer
     */
    private function createWishlistTable($installer)
    {
        $this->dropWishlistTableIfExists($installer);

        $wishlistTable = $installer->getConnection()->newTable(
            $installer->getTable('email_wishlist')
        );

        $wishlistTable = $this->addColumnsToWishlistTable($wishlistTable);
        $wishlistTable = $this->addIndexesToWishlistTable($installer, $wishlistTable);

        $wishlistTable->setComment('Connector Wishlist');
        $installer->getConnection()->createTable($wishlistTable);
    }

    /**
     * @param $installer
     */
    private function dropWishlistTableIfExists($installer)
    {
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_wishlist')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_wishlist')
            );
        }
    }

    /**
     * @param $wishlistTable
     * @return mixed
     */
    private function addColumnsToWishlistTable($wishlistTable)
    {
        return $wishlistTable->addColumn(
            'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ], 'Primary Key'
            )
            ->addColumn(
                'wishlist_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null, ['unsigned' => true, 'nullable' => false], 'Wishlist Id'
            )
            ->addColumn(
                'item_count', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null, ['unsigned' => true, 'nullable' => false], 'Item Count'
            )
            ->addColumn(
                'customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                11, ['unsigned' => true, 'nullable' => false], 'Customer ID'
            )
            ->addColumn(
                'store_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5,
                ['unsigned' => true, 'nullable' => false], 'Store Id'
            )
            ->addColumn(
                'wishlist_imported',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['unsigned' => true, 'nullable' => true], 'Wishlist Imported'
            )
            ->addColumn(
                'wishlist_modified',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['unsigned' => true, 'nullable' => true], 'Wishlist Modified'
            )
            ->addColumn(
                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Creation Time'
            )
            ->addColumn(
                'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Update Time'
            );
    }

    /**
     * @param $installer
     * @param $wishlistTable
     * @return mixed
     */
    private function addIndexesToWishlistTable($installer, $wishlistTable)
    {
        return $wishlistTable->addIndex(
            $installer->getIdxName(
                $installer->getTable('email_wishlist'), ['wishlist_id']
            ), ['wishlist_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_wishlist'), ['item_count']
                ), ['item_count']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_wishlist'), ['customer_id']
                ), ['customer_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_wishlist'),
                    ['wishlist_modified']
                ), ['wishlist_modified']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_wishlist'),
                    ['wishlist_imported']
                ), ['wishlist_imported']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_wishlist'), ['created_at']
                ), ['created_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_wishlist'), ['updated_at']
                ), ['updated_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_wishlist'), ['store_id']
                ), ['store_id']
            );
    }

    /**
     * @param $installer
     */
    private function createQuoteTable($installer)
    {
        $this->dropQuoteTableIfExists($installer);

        $quoteTable = $installer->getConnection()->newTable(
            $installer->getTable('email_quote'));

        $quoteTable = $this->addColumnsToQuoteTable($quoteTable);
        $quoteTable = $this->addIndexesToQuoteTable($installer, $quoteTable);

        $quoteTable->setComment('Connector Quotes');
        $installer->getConnection()->createTable($quoteTable);
    }

    /**
     * @param $installer
     */
    private function dropQuoteTableIfExists($installer)
    {
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_quote')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_quote')
            );
        }
    }

    /**
     * @param $quoteTable
     * @return mixed
     */
    private function addColumnsToQuoteTable($quoteTable)
    {
        return $quoteTable->addColumn(
            'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ], 'Primary Key'
            )
            ->addColumn(
                'quote_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 10,
                ['unsigned' => true, 'nullable' => false], 'Quote Id'
            )
            ->addColumn(
                'customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                11, ['unsigned' => true, 'nullable' => false], 'Customer ID'
            )
            ->addColumn(
                'store_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5,
                ['unsigned' => true, 'nullable' => false], 'Store Id'
            )
            ->addColumn(
                'imported', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null, ['unsigned' => true, 'nullable' => true], 'Quote Imported'
            )
            ->addColumn(
                'modified', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null, ['unsigned' => true, 'nullable' => true], 'Quote Modified'
            )
            ->addColumn(
                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Creation Time'
            )
            ->addColumn(
                'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Update Time'
            );
    }

    /**
     * @param $installer
     * @param $quoteTable
     * @return mixed
     */
    private function addIndexesToQuoteTable($installer, $quoteTable)
    {
        return $quoteTable->addIndex(
            $installer->getIdxName(
                $installer->getTable('email_quote'), ['quote_id']
            ), ['quote_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_quote'), ['customer_id']
                ), ['customer_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_quote'), ['store_id']
                ), ['store_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_quote'), ['imported']
                ), ['imported']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_quote'), ['modified']
                ), ['modified']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_quote'), ['created_at']
                ), ['created_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_quote'), ['created_at']
                ), ['created_at']
            );
    }

    /**
     * @param $installer
     */
    private function createCatalogTable($installer)
    {
        $this->dropCatalogTableIfExists($installer);

        $catalogTable = $installer->getConnection()->newTable(
            $installer->getTable('email_catalog'));

        $catalogTable = $this->addColumnsToCatalogTable($catalogTable);
        $catalogTable = $this->addIndexesToCatalogTable($installer, $catalogTable);

        $catalogTable->setComment('Connector Catalog');
        $installer->getConnection()->createTable($catalogTable);
    }

    /**
     * @param $installer
     */
    private function dropCatalogTableIfExists($installer)
    {
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_catalog')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_catalog')
            );
        }
    }

    /**
     * @param $catalogTable
     * @return mixed
     */
    private function addColumnsToCatalogTable($catalogTable)
    {
        return $catalogTable->addColumn(
            'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ], 'Primary Key'
        )
            ->addColumn(
                'product_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10,
                ['unsigned' => true, 'nullable' => false], 'Product Id'
            )
            ->addColumn(
                'imported', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null, ['unsigned' => true, 'nullable' => true],
                'Product Imported'
            )
            ->addColumn(
                'modified', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null, ['unsigned' => true, 'nullable' => true],
                'Product Modified'
            )
            ->addColumn(
                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Creation Time'
            )
            ->addColumn(
                'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Update Time'
            );
    }

    /**
     * @param $installer
     * @param $catalogTable
     * @return mixed
     */
    private function addIndexesToCatalogTable($installer, $catalogTable)
    {
        return $catalogTable->addIndex(
            $installer->getIdxName(
                $installer->getTable('email_catalog'), ['product_id']
            ), ['product_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_catalog'), ['imported']
                ), ['imported']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_catalog'), ['modified']
                ), ['modified']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_catalog'), ['created_at']
                ), ['created_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_catalog'), ['updated_at']
                ), ['updated_at']
            );
    }

    /**
     * @param $installer
     */
    private function createRuleTable($installer)
    {
        $this->dropRuleTableIfExists($installer);

        $ruleTable = $installer->getConnection()->newTable(
            $installer->getTable('email_rules'));

        $ruleTable = $this->addColumnsToRulesTable($ruleTable);

        $ruleTable->setComment('Connector Rules');
        $installer->getConnection()->createTable($ruleTable);
    }

    /**
     * @param $installer
     */
    private function dropRuleTableIfExists($installer)
    {
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_rules')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_rules')
            );
        }
    }

    /**
     * @param $ruleTable
     * @return mixed
     */
    private function addColumnsToRulesTable($ruleTable)
    {
        return $ruleTable->addColumn(
            'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ], 'Primary Key'
            )
            ->addColumn(
                'name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => ''], 'Rule Name'
            )
            ->addColumn(
                'website_ids', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => '0'], 'Website Id'
            )
            ->addColumn(
                'type', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['nullable' => false, 'default' => 0], 'Rule Type'
            )
            ->addColumn(
                'status', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['nullable' => false, 'default' => 0], 'Status'
            )
            ->addColumn(
                'combination', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null, ['nullable' => false, 'default' => '1'], 'Rule Condition'
            )
            ->addColumn(
                'condition', \Magento\Framework\DB\Ddl\Table::TYPE_BLOB, null,
                ['nullable' => false, 'default' => ''], 'Rule Condition'
            )
            ->addColumn(
                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Creation Time'
            )
            ->addColumn(
                'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Update Time'
            );
    }

    /**
     * @param $installer
     */
    private function createImporterTable($installer)
    {
        $this->dropImporterTableIfExists($installer);

        $importerTable = $installer->getConnection()->newTable(
            $installer->getTable('email_importer')
        );

        $importerTable = $this->addColumnsToImporterTable($importerTable);
        $importerTable = $this->addIndexesToImporterTable($installer, $importerTable);

        $importerTable ->setComment('Email Importer');
        $installer->getConnection()->createTable($importerTable);
    }

    /**
     * @param $installer
     */
    private function dropImporterTableIfExists($installer)
    {
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_importer')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_importer')
            );
        }
    }

    /**
     * @param $importerTable
     * @return mixed
     */
    private function addColumnsToImporterTable($importerTable)
    {
        return $importerTable->addColumn(
            'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ], 'Primary Key'
            )
            ->addColumn(
                'import_type', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => ''], 'Import Type'
            )
            ->addColumn(
                'website_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5,
                ['nullable' => false, 'default' => '0'], 'Website Id'
            )
            ->addColumn(
                'import_status', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null, ['nullable' => false, 'default' => 0], 'Import Status'
            )
            ->addColumn(
                'import_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => ''], 'Import Id'
            )
            ->addColumn(
                'import_data', \Magento\Framework\DB\Ddl\Table::TYPE_BLOB, '2M',
                ['nullable' => false, 'default' => ''], 'Import Data'
            )
            ->addColumn(
                'import_mode', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => ''], 'Import Mode'
            )
            ->addColumn(
                'import_file', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                ['nullable' => false, 'default' => ''], 'Import File'
            )
            ->addColumn(
                'message', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false, 'default' => ''], 'Error Message'
            )
            ->addColumn(
                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Creation Time'
            )
            ->addColumn(
                'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Update Time'
            )
            ->addColumn(
                'import_started',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, [],
                'Import Started'
            )
            ->addColumn(
                'import_finished',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, [],
                'Import Finished'
            );
    }

    /**
     * @param $installer
     * @param $importerTable
     * @return mixed
     */
    private function addIndexesToImporterTable($installer, $importerTable)
    {
        return $importerTable->addIndex(
            $installer->getIdxName(
                $installer->getTable('email_importer'), ['import_type']
            ), ['import_type']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_importer'), ['website_id']
                ), ['website_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_importer'), ['import_status']
                ), ['import_status']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_importer'), ['import_mode']
                ), ['import_mode']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_importer'), ['created_at']
                ), ['created_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_importer'), ['updated_at']
                ), ['updated_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_importer'), ['import_id']
                ), ['import_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_importer'), ['import_started']
                ), ['import_started']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_importer'), ['import_finished']
                ), ['import_finished']
            );
    }

    /**
     * @param $installer
     */
    private function createAutomationTable($installer)
    {
        $this->dropAutomationTableIfExists($installer);

        $automationTable = $installer->getConnection()->newTable(
            $installer->getTable('email_automation'));

        $automationTable = $this->addColumnsToAutomationTable($automationTable);
        $automationTable = $this->addIndexesToAutomationTable($installer, $automationTable);

        $automationTable->setComment('Automation Status');
        $installer->getConnection()->createTable($automationTable);
    }

    /**
     * @param $installer
     */
    private function dropAutomationTableIfExists($installer)
    {
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_automation')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_automation')
            );
        }
    }

    /**
     * @param $automationTable
     * @return mixed
     */
    private function addColumnsToAutomationTable($automationTable)
    {
        return $automationTable->addColumn(
            'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ], 'Primary Key'
            )
            ->addColumn(
                'automation_type', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255, ['nullable' => true], 'Automation Type'
            )
            ->addColumn(
                'store_name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => true], 'Automation Type'
            )
            ->addColumn(
                'enrolment_status', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255, ['nullable' => false], 'Entrolment Status'
            )
            ->addColumn(
                'email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => true], 'Email'
            )
            ->addColumn(
                'type_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => true], 'Type ID'
            )
            ->addColumn(
                'program_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => true], 'Program ID'
            )
            ->addColumn(
                'website_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5,
                ['unsigned' => true, 'nullable' => false], 'Website Id'
            )
            ->addColumn(
                'message', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false], 'Message'
            )
            ->addColumn(
                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Creation Time'
            )
            ->addColumn(
                'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Update Time'
            );
    }

    /**
     * @param $installer
     * @param $automationTable
     * @return mixed
     */
    private function addIndexesToAutomationTable($installer, $automationTable)
    {
        return $automationTable->addIndex(
            $installer->getIdxName(
                $installer->getTable('email_automation'),
                ['automation_type']
            ), ['automation_type']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_automation'),
                    ['enrolment_status']
                ), ['enrolment_status']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_automation'),
                    ['type_id']
                ), ['type_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_automation'),
                    ['email']
                ), ['email']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_automation'),
                    ['program_id']
                ), ['program_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_automation'),
                    ['created_at']
                ), ['created_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_automation'),
                    ['updated_at']
                ), ['updated_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable('email_automation'),
                    ['website_id']
                ), ['website_id']
            );
    }

    /**
     * @param $installer
     */
    private function populateEmailContactTable($installer)
    {
        $select = $installer->getConnection()->select()
            ->from(
                ['customer' => $installer->getTable('customer_entity')],
                [
                    'customer_id' => 'entity_id',
                    'email',
                    'website_id',
                    'store_id'
                ]
            );

        $insertArray = ['customer_id', 'email', 'website_id', 'store_id'];
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_contact'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);

        //Subscribers that are not customers
        $select = $installer->getConnection()->select()
            ->from(
                [
                    'subscriber' => $installer->getTable(
                        'newsletter_subscriber'
                    )
                ],
                [
                    'email' => 'subscriber_email',
                    'col2' => new \Zend_Db_Expr('1'),
                    'col3' => new \Zend_Db_Expr('1'),
                    'store_id',
                ]
            )
            ->where('customer_id =?', 0)
            ->where('subscriber_status =?', 1);
        $insertArray = [
            'email',
            'is_subscriber',
            'subscriber_status',
            'store_id'
        ];
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_contact'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);
    }

    /**
     * @param $installer
     */
    private function updateContactsWithCustomersThatAreSubscribers($installer)
    {
        //Update contacts with customers that are subscribers
        $select = $installer->getConnection()->select();
        $select->from(
            $installer->getTable('newsletter_subscriber'),
            'customer_id'
        )
            ->where('subscriber_status =?', 1)
            ->where('customer_id >?', 0);
        $customerIds = $select->getConnection()->fetchCol($select);

        if (!empty($customerIds)) {
            $customerIds = implode(', ', $customerIds);
            $installer->getConnection()->update(
                $installer->getTable('email_contact'),
                array(
                    'is_subscriber' => new \Zend_Db_Expr('1'),
                    'subscriber_status' => new \Zend_Db_Expr('1')
                ),
                ["customer_id in (?)" => $customerIds]
            );
        }
    }

    /**
     * @param $installer
     */
    private function populateEmailOrderTable($installer)
    {
        $select = $installer->getConnection()->select()
            ->from(
                $installer->getTable('sales_order'),
                [
                    'order_id' => 'entity_id',
                    'quote_id',
                    'store_id',
                    'created_at',
                    'updated_at',
                    'order_status' => 'status'
                ]
            );
        $insertArray = [
            'order_id',
            'quote_id',
            'store_id',
            'created_at',
            'updated_at',
            'order_status'
        ];
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_order'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);
    }

    /**
     * @param $installer
     */
    private function populateEmailReviewTable($installer)
    {
        $inCond = $installer->getConnection()->prepareSqlCondition(
            'review_detail.customer_id', ['notnull' => true]
        );
        $select = $installer->getConnection()->select()
            ->from(
                ['review' => $installer->getTable('review')],
                [
                    'review_id' => 'review.review_id',
                    'created_at' => 'review.created_at'
                ]
            )
            ->joinLeft(
                ['review_detail' => $installer->getTable('review_detail')],
                'review_detail.review_id = review.review_id',
                [
                    'store_id' => 'review_detail.store_id',
                    'customer_id' => 'review_detail.customer_id'
                ]
            )
            ->where($inCond);
        $insertArray = [
            'review_id',
            'created_at',
            'store_id',
            'customer_id'
        ];
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_review'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);
    }

    /**
     * @param $installer
     */
    private function populateEmailWishlistTable($installer)
    {
        $select = $installer->getConnection()->select()
            ->from(
                ['wishlist' => $installer->getTable('wishlist')],
                [
                    'wishlist_id',
                    'customer_id',
                    'created_at' => 'updated_at'
                ]
            )->joinLeft(
                ['ce' => $installer->getTable('customer_entity')],
                'wishlist.customer_id = ce.entity_id',
                ['store_id']
            )->joinInner(
                ['wi' => $installer->getTable('wishlist_item')],
                'wishlist.wishlist_id = wi.wishlist_id',
                ['item_count' => 'count(wi.wishlist_id)']
            )->group('wi.wishlist_id');

        $insertArray = [
            'wishlist_id',
            'customer_id',
            'created_at',
            'store_id',
            'item_count'
        ];
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_wishlist'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);
    }

    /**
     * @param $installer
     */
    private function populateEmailQuoteTable($installer)
    {
        $select = $installer->getConnection()->select()
            ->from(
                $installer->getTable('quote'),
                [
                    'quote_id' => 'entity_id',
                    'store_id',
                    'customer_id',
                    'created_at'
                ]
            )
            ->where('customer_id !=?', null)
            ->where('is_active =?', 1)
            ->where('items_count >?', 0);
        $insertArray = [
            'quote_id',
            'store_id',
            'customer_id',
            'created_at'
        ];
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_quote'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);
    }

    /**
     * @param $installer
     */
    private function populateEmailCatalogTable($installer)
    {
        $select = $installer->getConnection()->select()
            ->from(
                [
                    'catalog' => $installer->getTable(
                        'catalog_product_entity'
                    )
                ],
                [
                    'product_id' => 'catalog.entity_id',
                    'created_at' => 'catalog.created_at'
                ]
            );
        $insertArray = ['product_id', 'created_at'];
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_catalog'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);
    }

    /**
     * @param $installer
     */
    private function addColumnToAdminUserTable($installer)
    {
        $installer->getConnection()->addColumn(
            $installer->getTable('admin_user'), 'refresh_token', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 256,
                'nullable' => true,
                'default' => null,
                'comment' => 'Email connector refresh token',
            ]
        );
    }

    /**
     * @return mixed
     */
    private function saveValuesToConfig()
    {
        $configModel = ObjectManager::getInstance()->get(
            'Magento\Config\Model\ResourceModel\Config'
        );
        return $configModel;
    }

    /**
     * @param $configModel
     */
    private function saveAllOrderStatusesAsString($configModel)
    {
        $orderStatuses = ObjectManager::getInstance()->get(
            'Magento\Sales\Model\Config\Source\Order\Status'
        )->toOptionArray();
        if (count($orderStatuses) > 0 && $orderStatuses[0]['value'] == '') {
            array_shift($orderStatuses);
        }
        $options = [];
        foreach ($orderStatuses as $status) {
            $options[] = $status['value'];
        }
        $statusString = implode(',', $options);
        $configModel->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS,
            $statusString, 'website', 0
        );
    }

    /**
     * @param $configModel
     */
    private function saveAllProductTypesAsString($configModel)
    {
        $types = ObjectManager::getInstance()->create(
            'Magento\Catalog\Model\Product\Type'
        )->toOptionArray();
        $options = [];
        foreach ($types as $type) {
            $options[] = $type['value'];
        }
        $typeString = implode(',', $options);
        $configModel->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE,
            $typeString, 'website', '0'
        );
    }

    /**
     * @param $configModel
     */
    private function saveAllProductVisibilitiesAsString($configModel)
    {
        $visibilities = ObjectManager::getInstance()->create(
            'Magento\Catalog\Model\Product\Visibility'
        )->toOptionArray();
        $options = [];
        foreach ($visibilities as $visibility) {
            $options[] = $visibility['value'];
        }
        $visibilityString = implode(',', $options);
        $configModel->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY,
            $visibilityString, 'website', '0'
        );
    }
}
