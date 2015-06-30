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
		 * Create table 'email_catalog'
		 */
		$table = $installer->getConnection()
			->newTable($installer->getTable('email_catalog'))
			->addColumn(
			   'id',
			   \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			   null,
			   ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
			   'Email catalog code id'
			)
			->addColumn(
			   'product_id',
			   \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			   null,
			   ['unsigned' => true, 'nullable' => false],
			   'Product id'
			)
			->addColumn(
			   'imported',
			   \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
			   50,
			   [],
			   'Product Imported'
			)
			->addColumn(
			   'modified',
			   \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
			   null,
			   ['unsigned' => true, 'nullable' => false],
			   'Product Modified'
			)
			->addColumn(
			   'created_at',
			   \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
			   null,
			   [],
			   'Created At'
			)
			->addColumn(
				'updated at',
				\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
				null,
				[],
				'Updated At'
			)
			->addIndex(
			   $installer->getIdxName(
			       'email_catalog',
			       ['product_id', 'imported', 'modified'],
			       \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
			   ),
			   ['product_id', 'imported', 'modified'],
			   ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
			)
			->setComment('Email catalog products');
		$installer->getConnection()->createTable($table);

		$installer->endSetup();
	}
}
