<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
	{
		$installer = $setup;
		$installer->startSetup();


		/**
		 * create Contact table.
		 */
		$contactTable = $installer->getConnection()->newTable($installer->getTable('email_contact')
		)->addColumn('email_contact_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			'primary' => true,
			'identity' => true,
			'unsigned' => true,
			'nullable' => false
		), 'Primary Key')
              ->addColumn('is_guest', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
                  'unsigned' => true,
                  'nullable' => true,
              ), 'Is Guest')
              ->addColumn('contact_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
                  'unsigned' => true,
                  'nullable' => true,
              ), 'Connector Contact ID')
              ->addColumn('customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
                  'unsigned' => true,
                  'nullable' => false,
              ), 'Customer ID')
              ->addColumn('website_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
                  'unsigned' => true,
                  'nullable' => false,
                  'default' => '0'
              ), 'Website ID')
              ->addColumn('store_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
                  'unsigned' => true,
                  'nullable' => false,
                  'default' => '0'
              ), 'Store ID')
              ->addColumn('email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
                  'nullable' => false,
                  'default' => ''
              ), 'Customer Email')
              ->addColumn('is_subscriber', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
                  'unsigned' => true,
                  'nullable' => true,
              ), 'Is Subscriber')
              ->addColumn('subscriber_status', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
                  'unsigned' => true,
                  'nullable' => true,
              ), 'Subscriber status')
              ->addColumn('email_imported', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
                  'unsigned' => true,
                  'nullable' => true,
              ), 'Is Imported')
              ->addColumn('subscriber_imported', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
                  'unsigned' => true,
                  'nullable' => true,
              ), 'Subscriber Imported')
              ->addColumn('suppressed', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
                  'unsigned' => true,
                  'nullable' => true,
              ), 'Is Suppressed'
              )->addIndex(
				$installer->getIdxName('email_contact', ['email_contact_id']),
				['email_contact_id'])
                  ->addIndex($installer->getIdxName('email_contact', array('is_guest')),
                      array('is_guest'))
                  ->addIndex($installer->getIdxName('email_contact', array('customer_id')),
                      array('customer_id'))
                  ->addIndex($installer->getIdxName('email_contact', array('website_id')),
                      array('website_id'))
                  ->addIndex($installer->getIdxName('email_contact', array('is_subscriber')),
                      array('is_subscriber'))
                  ->addIndex($installer->getIdxName('email_contact', array('subscriber_status')),
                      array('subscriber_status'))
                  ->addIndex($installer->getIdxName('email_contact', array('email_imported')),
                      array('email_imported'))
                  ->addIndex($installer->getIdxName('email_contact', array('subscriber_imported')),
                      array('subscriber_imported'))
                  ->addIndex($installer->getIdxName('email_contact', array('suppressed')),
                      array('suppressed')
	                  //@todo fix the foreignkey : cannot add foreign key constraint
//                  )->addForeignKey(
//						$installer->getFkName('email_contact', 'website_id', 'store_website', 'website_id'),
//					'website_id',
//					$installer->getTable('store_website'),
//					'website_id',
//					\Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                  )->setComment('Connector Contacts');
		$installer->getConnection()->createTable($contactTable);


		/**
		 * Order table
		 */

		$orderTable = $installer->getConnection()->newTable($installer->getTable('email_order'));
		$orderTable->addColumn('email_order_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
				'primary' => true,
				'identity' => true,
				'unsigned' => true,
				'nullable' => false
			), 'Primary Key')
		      ->addColumn('order_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Order ID')
		      ->addColumn('order_status', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Order Status')
		      ->addColumn('quote_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Sales Quote ID')
		      ->addColumn('store_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
			      'default' => '0'
		      ), 'Store ID')
		      ->addColumn('email_imported', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Is Order Imported')
		      ->addColumn('modified', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Is Order Modified')
		      ->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Creation Time')
		      ->addColumn('updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Update Time')
		      ->addIndex($installer->getIdxName($installer->getTable('email_order'), array('store_id')),
			      array('store_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_order'), array('quote_id')),
			      array('quote_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_order'), array('email_imported')),
			      array('email_imported'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_order'), array('order_status')),
			      array('order_status'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_order'), array('modified')),
			      array('modified'))
//		      ->addForeignKey(
//			      $installer->getFkName($orderTable, 'store_id', 'core/store', 'store_id'),
//			      'store_id', $installer->getTable('core/store'), 'store_id',
//			      \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE, \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE)
		      ->setComment('Transactional Orders Data');
		$installer->getConnection()->createTable($orderTable);


		/**
		 * Campaign table.
		 */
		$campaignTable = $installer->getConnection()->newTable($installer->getTable('email_campaign'));
		$campaignTable->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
				'primary' => true,
				'identity' => true,
				'unsigned' => true,
				'nullable' => false
			), 'Primary Key')
		      ->addColumn('campaign_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Campaign ID')
		      ->addColumn('email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Contact Email')
		      ->addColumn('customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Customer ID')
		      ->addColumn('is_sent', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Is Sent')
		      ->addColumn('sent_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Send Date')
		      ->addColumn('order_increment_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Order Increment ID')
		      ->addColumn('quote_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Sales Quote ID')
		      ->addColumn('message', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Errror Message')
		      ->addColumn('checkout_method', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Checkout Method Used')
		      ->addColumn('store_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
			      'default' => '0'
		      ), 'Store ID')
		      ->addColumn('event_name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Event Name')
		      ->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Creation Time')
		      ->addColumn('updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Update Time')
		      ->addIndex($installer->getIdxName($installer->getTable('email_campaign'), array('store_id')),
			      array('store_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_campaign'), array('campaign_id')),
			      array('campaign_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_campaign'), array('email')),
			      array('email'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_campaign'), array('is_sent')),
			      array('is_sent'))
//		      ->addForeignKey(
//			      $installer->getFkName($campaignTable, 'store_id', 'core/store', 'store_id'),
//			      'store_id', $installer->getTable('core/store'), 'store_id',
//			      \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE, \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE)
		      ->setComment('Connector Campaigns');
		$installer->getConnection()->createTable($campaignTable);


//@todo install the admin notification
///**
//		 Admin notification message
//
//$adminData = array();
//$adminData[] = array(
//	'severity' => 4,
//	'date_added' => gmdate('Y-m-d H:i:s', time()),
//	'title' => 'Email Connector Was Installed. Please Enter Your API Credentials & Ensure Cron Jobs Are Running On Your Site (Find Out More)',
//	'description' => 'Email Connector Was Installed. Please Enter Your API Credentials & Ensure Cron Jobs Are Running On Your Site.',
//	'url' => 'http://www.magentocommerce.com/wiki/1_-_installation_and_configuration/how_to_setup_a_cron_job'
//);

		/**
		 * Populate tables
		 */
		$select = $installer->getConnection()->select()
            ->from(
                array('customer' => $installer->getTable('customer_entity')),
                array('customer_id' => 'entity_id', 'email', 'website_id', 'store_id')
            );

		$insertArray = array('customer_id', 'email', 'website_id', 'store_id');
		$sqlQuery = $select->insertFromSelect($installer->getTable('email_contact'), $insertArray, false);
		$installer->getConnection()->query($sqlQuery);

		// subscribers that are not customers
		$select = $installer->getConnection()->select()
            ->from(
                array('subscriber' => $installer->getTable('newsletter_subscriber')),
                array(
                    'email' => 'subscriber_email',
                    'col2' => new \Zend_Db_Expr('1'),
                    'col3' => new \Zend_Db_Expr('1'),
                    'store_id'
                )
            )
            ->where('customer_id =?', 0)
            ->where('subscriber_status =?', 1);
		$insertArray = array('email', 'is_subscriber', 'subscriber_status', 'store_id');
		$sqlQuery = $select->insertFromSelect($installer->getTable('email_contact'), $insertArray, false);
		$installer->getConnection()->query($sqlQuery);


		//Insert and populate email order the table
		$select = $installer->getConnection()->select()
            ->from(
                $installer->getTable('sales_order'),
                array('order_id' => 'entity_id', 'quote_id', 'store_id', 'created_at', 'updated_at', 'order_status' => 'status')
            );

		$insertArray =
			array('order_id', 'quote_id', 'store_id', 'created_at', 'updated_at', 'order_status');

		$sqlQuery = $select->insertFromSelect($installer->getTable('email_order'), $insertArray, false);
		$installer->getConnection()->query($sqlQuery);


		//@todo Save all order statuses as string
//		$source = Mage::getModel('adminhtml/system_config_source_order_status');
//		$statuses = $source->toOptionArray();
//		if (count($statuses) > 0 && $statuses[0]['value'] == '')
//			array_shift($statuses);
//		$options = array();
//		foreach ($statuses as $status) {
//			$options[] = $status['value'];
//		}
//		$statusString = implode(',', $options);
//		$configModel = Mage::getModel('core/config');
//		$configModel->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS, $statusString);

		//OAUTH refresh token
		$installer->getConnection()->addColumn($installer->getTable('admin_user'), 'refresh_token', array(
			'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
			'length' => 256,
			'nullable' => true,
			'default' => null,
			'comment' => 'Email connector refresh token'
		));
		//increase the increment id column
		$installer->getConnection()->modifyColumn($installer->getTable('email_campaign'), 'order_increment_id', 'VARCHAR(50)');

		//Insert status column to email_order table
		$installer->getConnection()->addColumn($installer->getTable('email_order'), 'order_status', array(
			'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
			'length' => 256,
			'nullable' => false,
			'default' => null,
			'comment' => 'Order Status'
		));
		$select = $installer->getConnection()->select();
		$select->joinLeft(
			array('sfo' => $installer->getTable('sales_order')),
			"eo.order_id = sfo.entity_id",
			array('order_status' => 'sfo.status')
		);
		$updateSql = $select->crossUpdateFromSelect(array('eo' => $installer->getTable('email_order')));
		$installer->getConnection()->query($updateSql);

		//@todo Save all order statuses as string to extension's config value
//		$source = Mage::getModel('adminhtml/system_config_source_order_status');
//		$statuses = $source->toOptionArray();
//		if (count($statuses) > 0 && $statuses[0]['value'] == '')
//			array_shift($statuses);
//		$options = array();
//		foreach ($statuses as $status) {
//			$options[] = $status['value'];
//		}
//		$statusString = implode(',', $options);
//		$configModel = Mage::getModel('core/config');
//		$configModel->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS, $statusString);



		/**
		 * create review table.
		 */
		$reviewTable = $installer->getConnection()->newTable($installer->getTable('email_review'));
		$reviewTable->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
				'primary' => true,
				'identity' => true,
				'unsigned' => true,
				'nullable' => false
			), 'Primary Key')
		      ->addColumn('review_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Review Id')
		      ->addColumn('customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Customer ID')
		      ->addColumn('store_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Store Id')
		      ->addColumn('review_imported', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Review Imported')
		      ->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Creation Time')
		      ->addColumn('updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Update Time')
		      ->addIndex($installer->getIdxName($installer->getTable('email_review'), array('review_id')),
			      array('review_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_review'), array('customer_id')),
			      array('customer_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_review'), array('store_id')),
			      array('store_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_review'), array('review_imported')),
			      array('review_imported'))
		      ->setComment('Connector Reviews');
		$installer->getConnection()->createTable($reviewTable);

		//populate review table.
		$inCond = $installer->getConnection()->prepareSqlCondition('review_detail.customer_id', array('notnull' => true));
		$select = $installer->getConnection()->select()
            ->from(
                array('review' => $installer->getTable('review')),
                array('review_id' => 'review.review_id', 'created_at' => 'review.created_at')
            )
            ->joinLeft(
                array('review_detail' => $installer->getTable('review_detail')),
                "review_detail.review_id = review.review_id",
                array('store_id' => 'review_detail.store_id', 'customer_id' => 'review_detail.customer_id')
            )
            ->where($inCond);

		$insertArray = array('review_id', 'created_at', 'store_id', 'customer_id');
		$sqlQuery = $select->insertFromSelect($installer->getTable('email_review'), $insertArray, false);
		$installer->getConnection()->query($sqlQuery);

		//add columns to table
		$installer->getConnection()->addColumn($installer->getTable('email_campaign'), 'from_address', array(
			'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
			'unsigned' => true,
			'nullable' => true,
			'default' => null,
			'comment' => 'Email From Address'
		));
		$installer->getConnection()->addColumn($installer->getTable('email_campaign'), 'attachment_id', array(
			'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			'unsigned' => true,
			'nullable' => true,
			'default' => null,
			'comment' => 'Attachment Id'
		));


		/**
		 * create wishlist table.
		 */
		$wishlistTable = $installer->getConnection()->newTable($installer->getTable('email_wishlist'));
		$wishlistTable->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
				'primary' => true,
				'identity' => true,
				'unsigned' => true,
				'nullable' => false
			), 'Primary Key')
		      ->addColumn('wishlist_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Wishlist Id')
		      ->addColumn('item_count', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Item Count')
		      ->addColumn('customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Customer ID')
		      ->addColumn('store_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Store Id')
		      ->addColumn('wishlist_imported', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Wishlist Imported')
		      ->addColumn('wishlist_modified', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Wishlist Modified')
		      ->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Creation Time')
		      ->addColumn('updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Update Time')
		      ->addIndex($installer->getIdxName($installer->getTable('email_wishlist'), array('wishlist_id')),
			      array('wishlist_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_wishlist'), array('item_count')),
			      array('item_count'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_wishlist'), array('customer_id')),
			      array('customer_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_wishlist'), array('wishlist_modified')),
			      array('wishlist_modified'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_wishlist'), array('wishlist_imported')),
			      array('wishlist_imported'))
		      ->setComment('Connector Wishlist');
		$installer->getConnection()->createTable($wishlistTable);

		//wishlist populate
		$select = $installer->getConnection()->select()
            ->from(
                array('wishlist' => $installer->getTable('wishlist')),
                array('wishlist_id', 'customer_id', 'created_at' => 'updated_at')
            )->joinLeft(
			array('ce' => $installer->getTable('customer_entity')),
				"wishlist.customer_id = ce.entity_id",
				array('store_id')
			)->joinInner(
				array('wi' => $installer->getTable('wishlist_item')),
				"wishlist.wishlist_id = wi.wishlist_id",
				array('item_count' => 'count(wi.wishlist_id)')
			)->group('wi.wishlist_id');

		$insertArray = array('wishlist_id', 'customer_id', 'created_at', 'store_id', 'item_count');
		$sqlQuery = $select->insertFromSelect($installer->getTable('email_wishlist'), $insertArray, false);
		$installer->getConnection()->query($sqlQuery);


		/**
		 * create quote table.
		 */
		$quoteTable = $installer->getConnection()->newTable($installer->getTable('email_quote'));
		$quoteTable->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
				'primary' => true,
				'identity' => true,
				'unsigned' => true,
				'nullable' => false
			), 'Primary Key')
		      ->addColumn('quote_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Quote Id')
		      ->addColumn('customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Customer ID')
		      ->addColumn('store_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Store Id')
		      ->addColumn('imported', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Quote Imported')
		      ->addColumn('modified', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Quote Modified')
		      ->addColumn('converted_to_order', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Quote Converted To Order')
		      ->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Creation Time')
		      ->addColumn('updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Update Time')
		      ->addIndex($installer->getIdxName($installer->getTable('email_quote'), array('quote_id')),
			      array('quote_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_quote'), array('customer_id')),
			      array('customer_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_quote'), array('store_id')),
			      array('store_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_quote'), array('imported')),
			      array('imported'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_quote'), array('modified')),
			      array('modified'))
		      ->setComment('Connector Quotes');
		$installer->getConnection()->createTable($quoteTable);

		//populate quote table
		$select = $installer->getConnection()->select()
            ->from(
                $installer->getTable('quote'),
                array('quote_id' => 'entity_id', 'store_id', 'customer_id', 'created_at')
            )
            ->where('customer_id !=?', NULL)
            ->where('is_active =?', 1)
            ->where('items_count >?', 0);

		$insertArray = array('quote_id', 'store_id', 'customer_id', 'created_at');
		$sqlQuery = $select->insertFromSelect($installer->getTable('email_quote'), $insertArray, false);
		$installer->getConnection()->query($sqlQuery);


		/**
		 * create catalog table.
		 */
		$catalogTable = $installer->getConnection()->newTable($installer->getTable('email_catalog'));
		$catalogTable->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
				'primary' => true,
				'identity' => true,
				'unsigned' => true,
				'nullable' => false
			), 'Primary Key')
		      ->addColumn('product_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned' => true,
			      'nullable' => false,
		      ), 'Product Id')
		      ->addColumn('imported', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Product Imported')
		      ->addColumn('modified', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'unsigned' => true,
			      'nullable' => true,
		      ), 'Product Modified')
		      ->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Creation Time')
		      ->addColumn('updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Update Time')
		      ->addIndex($installer->getIdxName($installer->getTable('email_catalog'), array('product_id')),
			      array('product_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_catalog'), array('imported')),
			      array('imported'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_catalog'), array('modified')),
			      array('modified'))
		      ->setComment('Connector Catalog');
		$installer->getConnection()->createTable($catalogTable);

		//Populate catalog table
		$select = $installer->getConnection()->select()
            ->from(
                array('catalog' => $installer->getTable('catalog_product_entity')),
                array('product_id' => 'catalog.entity_id', 'created_at' => 'catalog.created_at')
            );
		$insertArray = array('product_id', 'created_at');
		$sqlQuery = $select->insertFromSelect($installer->getTable('email_catalog'), $insertArray, false);
		$installer->getConnection()->query($sqlQuery);


		/**
		 * create rules table.
		 */
		$ruleTable = $installer->getConnection()->newTable($installer->getTable('email_rules'));
		$ruleTable->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
				'primary' => true,
				'identity' => true,
				'unsigned' => true,
				'nullable' => false
			), 'Primary Key')
		      ->addColumn('name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Rule Name')
		      ->addColumn('website_ids', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => '0'
		      ), 'Website Id')
		      ->addColumn('type', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'nullable' => false,
			      'default' => 0
		      ), 'Rule Type')
		      ->addColumn('status', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'nullable' => false,
			      'default' => 0
		      ), 'Status')
		      ->addColumn('combination', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'nullable' => false,
			      'default' => '1'
		      ), 'Rule Condition')
		      ->addColumn('condition', \Magento\Framework\DB\Ddl\Table::TYPE_BLOB, null, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Rule Condition')
		      ->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Creation Time')
		      ->addColumn('updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Update Time')
		      ->setComment('Connector Rules');
		$installer->getConnection()->createTable($ruleTable);



		//@todo config catalog types
//		$configModel = Mage::getModel('core/config');
////Save all product types as string to extension's config value
//		$types = Mage::getModel('ddg_automation/adminhtml_source_sync_catalog_type')->toOptionArray();
//		$options = array();
//		foreach ($types as $type) {
//			$options[] = $type['value'];
//		}
//		$typeString = implode(',', $options);
//
////Save all product visibilities as string to extension's config value
//		$visibilities = Mage::getModel('ddg_automation/adminhtml_source_sync_catalog_visibility')->toOptionArray();
//		$options = array();
//		foreach ($visibilities as $visibility) {
//			$options[] = $visibility['value'];
//		}
//		$visibilityString = implode(',', $options);
//
////save config value
//		$configModel->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE, $typeString);
//		$configModel->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY, $visibilityString);


		/**
		 * create email importer table.
		 */
		$importerTable = $installer->getConnection()->newTable($installer->getTable('email_importer'));
		$importerTable->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
				'primary' => true,
				'identity' => true,
				'unsigned' => true,
				'nullable' => false
			), 'Primary Key')
		      ->addColumn('import_type', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Import Type')
		      ->addColumn('website_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => '0'
		      ), 'Website Id')
		      ->addColumn('import_status', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
			      'nullable' => false,
			      'default' => 0
		      ), 'Import Status')
		      ->addColumn('import_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Import Id')
		      ->addColumn('import_data', \Magento\Framework\DB\Ddl\Table::TYPE_BLOB, '2M', array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Import Data')
		      ->addColumn('import_mode', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Import Mode')
		      ->addColumn('import_file', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Import File')
		      ->addColumn('message', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => false,
			      'default' => ''
		      ), 'Error Message')
		      ->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Creation Time')
		      ->addColumn('updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Update Time')
		      ->addColumn('import_started', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Import Started')
		      ->addColumn('import_finished', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(), 'Import Finished')
		      ->addIndex($installer->getIdxName($installer->getTable('email_importer'), array('import_type')),
			      array('import_type'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_importer'), array('website_id')),
			      array('website_id'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_importer'), array('import_status')),
			      array('import_status'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_importer'), array('import_mode')),
			      array('import_mode'))
		      ->setComment('Email Importer');
		$installer->getConnection()->createTable($importerTable);


		/**
		 * modify email_quote table
		 */
		$quoteTable = $installer->getTable('email_quote');
		$installer->getConnection()->dropColumn($quoteTable, 'converted_to_order');

		/**
		 * Create automation table.
		 */
		$automationTable = $installer->getConnection()->newTable($installer->getTable('email_automation'));
		$automationTable->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
				'primary'  => true,
				'identity' => true,
				'unsigned' => true,
				'nullable' => false
			), 'Primary Key')
		      ->addColumn('automation_type', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => true,
		      ), 'Automation Type')
		      ->addColumn('store_name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable' => true,
		      ), 'Automation Type')
		      ->addColumn('enrolment_status', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable'  => false,
		      ), 'Entrolment Status')
		      ->addColumn('email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable'  => true,
		      ), 'Email')
		      ->addColumn('type_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable'  => true,
		      ), 'Type ID')
		      ->addColumn('program_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable'  => true,
		      ), 'Program ID')
		      ->addColumn('website_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
			      'unsigned'  => true,
			      'nullable' => false,
		      ), 'Website Id')
		      ->addColumn('message', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, array(
			      'nullable'  => false,
		      ), 'Message')
		      ->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(
		      ), 'Creation Time')
		      ->addColumn('updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(
		      ), 'Update Time')
		      ->addIndex($installer->getIdxName($installer->getTable('email_automation'), array('automation_type')),
			      array('automation_type'))
		      ->addIndex($installer->getIdxName($installer->getTable('email_automation'), array('enrolment_status')),
			      array('enrolment_status'))
		      ->setComment('Automation Status');
		$installer->getConnection()->createTable($automationTable);

		$installer->endSetup();
	}
}
