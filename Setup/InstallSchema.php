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

        /*
         * Contact table.
         */
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_contact')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_contact')
            );
        }

        $contactTable = $installer->getConnection()->newTable(
            $installer->getTable('email_contact')
        )
            ->addColumn(
                'email_contact_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
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
            )
            ->addIndex(
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
            ->addForeignKey(
                $installer->getFkName(
                    'email_contact', 'website_id', 'store_website', 'website_id'
                ),
                'website_id',
                $installer->getTable('store_website'),
                'website_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Connector Contacts');
        //create table
        $installer->getConnection()->createTable($contactTable);

        /*
         * Order table.
         */
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_order')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_order')
            );
        }
        $orderTable = $installer->getConnection()->newTable(
            $installer->getTable('email_order')
        )
            ->addColumn(
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
            )
            ->addIndex(
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
            ->addForeignKey(
                $installer->getFkName(
                    $installer->getTable('email_order'), 'store_id',
                    'core/store', 'store_id'
                ),
                'store_id',
                $installer->getTable('store'), 'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE,
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Transactional Order Data');
        //create table
        $installer->getConnection()->createTable($orderTable);

        /*
         * Campaign table.
         */
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_campaign')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_campaign')
            );
        }

        $campaignTable = $installer->getConnection()->newTable(
            $installer->getTable('email_campaign')
        )
            ->addColumn(
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
                'is_sent', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['unsigned' => true, 'nullable' => true], 'Is Sent'
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
                ['nullable' => false, 'default' => ''], 'Errror Message'
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
                'from_address', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
                'Email From Address'
            )
            ->addColumn(
                'attachment_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
                'Attachment Id'
            )
            ->addColumn(
                'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Creation Time'
            )
            ->addColumn(
                'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null, [], 'Update Time'
            )
            ->addIndex(
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
                    $installer->getTable('email_campaign'), ['is_sent']
                ), ['is_sent']
            )
            ->addForeignKey(
                $installer->getFkName(
                    $installer->getTable('email_campaign'), 'store_id',
                    'core/store', 'store_id'
                ),
                'store_id',
                $installer->getTable('store'), 'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE,
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Connector Campaigns');
        //create table
        $installer->getConnection()->createTable($campaignTable);

        /*
         * Populate tables
         */
        $select = $installer->getConnection()->select()
            ->from(
                array('customer' => $installer->getTable('customer_entity')),
                array(
                    'customer_id' => 'entity_id',
                    'email',
                    'website_id',
                    'store_id'
                )
            );

        $insertArray = array('customer_id', 'email', 'website_id', 'store_id');
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_contact'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);

        // subscribers that are not customers
        $select = $installer->getConnection()->select()
            ->from(
                array(
                    'subscriber' => $installer->getTable(
                        'newsletter_subscriber'
                    )
                ),
                array(
                    'email' => 'subscriber_email',
                    'col2' => new \Zend_Db_Expr('1'),
                    'col3' => new \Zend_Db_Expr('1'),
                    'store_id',
                )
            )
            ->where('customer_id =?', 0)
            ->where('subscriber_status =?', 1);
        $insertArray = array(
            'email',
            'is_subscriber',
            'subscriber_status',
            'store_id'
        );
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_contact'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);

        //Insert and populate email order the table
        $select = $installer->getConnection()->select()
            ->from(
                $installer->getTable('sales_order'),
                array(
                    'order_id' => 'entity_id',
                    'quote_id',
                    'store_id',
                    'created_at',
                    'updated_at',
                    'order_status' => 'status'
                )
            );
        $insertArray = array(
            'order_id',
            'quote_id',
            'store_id',
            'created_at',
            'updated_at',
            'order_status'
        );
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_order'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);

        //config value in configuration data for all order statuses
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
        $configModel = ObjectManager::getInstance()->get(
            'Magento\Config\Model\ResourceModel\Config'
        );
        $configModel->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS,
            $statusString, $scope = 'website', $scopeId = 0
        );

        //OAUTH refresh token
        $installer->getConnection()->addColumn(
            $installer->getTable('admin_user'), 'refresh_token', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 256,
                'nullable' => true,
                'default' => null,
                'comment' => 'Email connector refresh token',
            ]
        );

        $select = $installer->getConnection()->select();
        $select->joinLeft(
            array('sfo' => $installer->getTable('sales_order')),
            'eo.order_id = sfo.entity_id',
            array('order_status' => 'sfo.status')
        );
        $updateSql = $select->crossUpdateFromSelect(
            array('eo' => $installer->getTable('email_order'))
        );
        $installer->getConnection()->query($updateSql);

        /*
         * Review table
         */
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_review')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_review')
            );
        }
        $reviewTable = $installer->getConnection()->newTable(
            $installer->getTable('email_review')
        )
            ->addColumn(
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
            )
            ->addIndex(
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
            ->setComment('Connector Reviews');
        //create table
        $installer->getConnection()->createTable($reviewTable);

        //populate review table.
        $inCond = $installer->getConnection()->prepareSqlCondition(
            'review_detail.customer_id', array('notnull' => true)
        );
        $select = $installer->getConnection()->select()
            ->from(
                array('review' => $installer->getTable('review')),
                array(
                    'review_id' => 'review.review_id',
                    'created_at' => 'review.created_at'
                )
            )
            ->joinLeft(
                array('review_detail' => $installer->getTable('review_detail')),
                'review_detail.review_id = review.review_id',
                array(
                    'store_id' => 'review_detail.store_id',
                    'customer_id' => 'review_detail.customer_id'
                )
            )
            ->where($inCond);
        $insertArray = array(
            'review_id',
            'created_at',
            'store_id',
            'customer_id'
        );
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_review'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);

        /*
         * Wishlist table.
         */
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_wishlist')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_wishlist')
            );
        }
        $wishlistTable = $installer->getConnection()->newTable(
            $installer->getTable('email_wishlist')
        )
            ->addColumn(
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
            )
            ->addIndex(
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
            ->setComment('Connector Wishlist');
        //create table
        $installer->getConnection()->createTable($wishlistTable);

        //wishlist populate
        $select = $installer->getConnection()->select()
            ->from(
                array('wishlist' => $installer->getTable('wishlist')),
                array(
                    'wishlist_id',
                    'customer_id',
                    'created_at' => 'updated_at'
                )
            )->joinLeft(
                array('ce' => $installer->getTable('customer_entity')),
                'wishlist.customer_id = ce.entity_id',
                array('store_id')
            )->joinInner(
                array('wi' => $installer->getTable('wishlist_item')),
                'wishlist.wishlist_id = wi.wishlist_id',
                array('item_count' => 'count(wi.wishlist_id)')
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

        /*
         * Quote table.
         */
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_quote')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_quote')
            );
        }
        $quoteTable = $installer->getConnection()->newTable(
            $installer->getTable('email_quote')
        )
            ->addColumn(
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
            )
            ->addIndex(
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
            ->setComment('Connector Quotes');
        //create table
        $installer->getConnection()->createTable($quoteTable);

        //populate quote table
        $select = $installer->getConnection()->select()
            ->from(
                $installer->getTable('quote'),
                array(
                    'quote_id' => 'entity_id',
                    'store_id',
                    'customer_id',
                    'created_at'
                )
            )
            ->where('customer_id !=?', null)
            ->where('is_active =?', 1)
            ->where('items_count >?', 0);
        $insertArray = array(
            'quote_id',
            'store_id',
            'customer_id',
            'created_at'
        );
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_quote'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);

        /*
         * Catalog table.
         */
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_catalog')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_catalog')
            );
        }
        $catalogTable = $installer->getConnection()->newTable(
            $installer->getTable('email_catalog')
        )
            ->addColumn(
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
            )
            ->addIndex(
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
            ->setComment('Connector Catalog');
        //create table
        $installer->getConnection()->createTable($catalogTable);

        //Populate catalog table
        $select = $installer->getConnection()->select()
            ->from(
                array(
                    'catalog' => $installer->getTable(
                        'catalog_product_entity'
                    )
                ),
                array(
                    'product_id' => 'catalog.entity_id',
                    'created_at' => 'catalog.created_at'
                )
            );
        $insertArray = array('product_id', 'created_at');
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable('email_catalog'), $insertArray, false
        );
        $installer->getConnection()->query($sqlQuery);

        /*
         * create rules table.
         */
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_rules')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_rules')
            );
        }
        $ruleTable = $installer->getConnection()->newTable(
            $installer->getTable('email_rules')
        )
            ->addColumn(
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
            )
            ->setComment('Connector Rules');
        //create table
        $installer->getConnection()->createTable($ruleTable);

        //Save all product types as string to extension's config value
        $types = ObjectManager::getInstance()->create(
            'Magento\Catalog\Model\Product\Type'
        )->toOptionArray();
        $options = array();
        foreach ($types as $type) {
            $options[] = $type['value'];
        }
        $typeString = implode(',', $options);
        //Save all product visibilities as string to extension's config value
        $visibilities = ObjectManager::getInstance()->create(
            'Magento\Catalog\Model\Product\Visibility'
        )->toOptionArray();
        $options = array();
        foreach ($visibilities as $visibility) {
            $options[] = $visibility['value'];
        }
        $visibilityString = implode(',', $options);
        //save catalog type and catalog visibility into the config table
        $configModel->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE,
            $typeString, $scope = 'website', $scopeId = '0'
        );
        $configModel->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY,
            $visibilityString, $scope = 'website', $scopeId = '0'
        );

        /*
         * Email importer table.
         */
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_importer')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_importer')
            );
        }
        $importerTable = $installer->getConnection()->newTable(
            $installer->getTable('email_importer')
        )
            ->addColumn(
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
            )
            ->addIndex(
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
            ->setComment('Email Importer');
        //create table
        $installer->getConnection()->createTable($importerTable);

        /*
         * Automation table.
         */
        if ($installer->getConnection()->isTableExists(
            $installer->getTable('email_automation')
        )
        ) {
            $installer->getConnection()->dropTable(
                $installer->getTable('email_automation')
            );
        }
        $automationTable = $installer->getConnection()->newTable(
            $installer->getTable('email_automation')
        )
            ->addColumn(
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
            )
            ->addIndex(
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
            ->setComment('Automation Status');
        //create table
        $installer->getConnection()->createTable($automationTable);

        $installer->endSetup();
    }
}
