<?php

namespace Dotdigitalgroup\Email\Setup\Schema;

use \Magento\Framework\Setup\SchemaSetupInterface;

class Shared
{
    /**
     * Create abandoned cart table
     *
     * @param SchemaSetupInterface $installer
     * @param string $tableName
     */
    public function createAbandonedCartTable($installer, $tableName)
    {
        $abandonedCartTable = $installer->getConnection()->newTable($installer->getTable($tableName));
        $abandonedCartTable = $this->addColumnForAbandonedCartTable($abandonedCartTable);
        $abandonedCartTable = $this->addIndexKeyForAbandonedCarts($installer, $abandonedCartTable);
        $abandonedCartTable->setComment('Abandoned Carts Table');
        $installer->getConnection()->createTable($abandonedCartTable);
    }

    /**
     * @param \Magento\Framework\DB\Ddl\Table $table
     * @return mixed
     */
    private function addColumnForAbandonedCartTable($table)
    {
        return $table->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false
            ],
            'Primary Key'
        )
            ->addColumn(
                'quote_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Quote Id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                10,
                ['unsigned' => true, 'nullable' => true],
                'Store Id'
            )
            ->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
                'Customer ID'
            )
            ->addColumn(
                'email',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Email'
            )
            ->addColumn(
                'is_active',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                'Quote Active'
            )
            ->addColumn(
                'quote_updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'Quote updated at'
            )
            ->addColumn(
                'abandoned_cart_number',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Abandoned Cart number'
            )
            ->addColumn(
                'items_count',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => 0],
                'Quote items count'
            )
            ->addColumn(
                'items_ids',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => true],
                'Quote item ids'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'Updated at'
            );
    }

    /**
     * @param SchemaSetupInterface $installer
     * @param \Magento\Framework\DB\Ddl\Table $abandonedCartTable
     * @return mixed
     */
    private function addIndexKeyForAbandonedCarts($installer, $abandonedCartTable)
    {
        return $abandonedCartTable->addIndex(
            $installer->getIdxName('email_abandoned_cart', ['quote_id']),
            ['quote_id']
        )
            ->addIndex(
                $installer->getIdxName('email_abandoned_cart', ['store_id']),
                ['store_id']
            )
            ->addIndex(
                $installer->getIdxName('email_abandoned_cart', ['customer_id']),
                ['customer_id']
            )
            ->addIndex(
                $installer->getIdxName('email_abandoned_cart', ['email']),
                ['email']
            );
    }

    /**
     * Create consent table
     *
     * @param SchemaSetupInterface $installer
     * @param string $tableName
     */
    public function createConsentTable($installer, $tableName)
    {
        $emailContactConsentTable = $installer->getConnection()->newTable($installer->getTable($tableName));
        $emailContactConsentTable = $this->addColumnForConsentTable($emailContactConsentTable);
        $emailContactConsentTable = $this->addIndexToConsentTable($installer, $emailContactConsentTable);
        $emailContactConsentTable = $this->addKeyForConsentTable($installer, $emailContactConsentTable);
        $emailContactConsentTable->setComment('Email contact consent table.');
        $installer->getConnection()->createTable($emailContactConsentTable);
    }

    /**
     * @param \Magento\Framework\DB\Ddl\Table $emailContactConsentTable
     * @return mixed
     */
    private function addColumnForConsentTable($emailContactConsentTable)
    {
        $emailContactConsentTable
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                [
                    'primary' => true,
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Primary Key'
            )
            ->addColumn(
                'email_contact_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Email Contact Id'
            )
            ->addColumn(
                'consent_url',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => true],
                'Contact consent url'
            )
            ->addColumn(
                'consent_datetime',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'Contact consent datetime'
            )
            ->addColumn(
                'consent_ip',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => true],
                'Contact consent ip'
            )
            ->addColumn(
                'consent_user_agent',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => true],
                'Contact consent user agent'
            );

        return $emailContactConsentTable;
    }

    /**
     * @param SchemaSetupInterface $installer
     * @param \Magento\Framework\DB\Ddl\Table $emailContactConsentTable
     * @return mixed
     */
    private function addKeyForConsentTable($installer, $emailContactConsentTable)
    {
        return $emailContactConsentTable->addForeignKey(
            $installer->getFkName('email_contact_consent', 'email_contact_id', 'email_contact', 'email_contact_id'),
            'email_contact_id',
            $installer->getTable('email_contact'),
            'email_contact_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     * @param \Magento\Framework\DB\Ddl\Table $table
     * @return mixed]
     */
    private function addIndexToConsentTable($installer, $table)
    {
        return $table->addIndex(
            $installer->getIdxName($installer->getTable('email_contact_consent'), ['email_contact_id']),
            ['email_contact_id']
        );
    }

    /**
     * Create failed auth table
     *
     * @param SchemaSetupInterface $installer
     * @param string $tableName
     */
    public function createFailedAuthTable($installer, $tableName)
    {
        $emailAuthEdc = $installer->getConnection()->newTable($installer->getTable($tableName));
        $emailAuthEdc = $this->addColumnForFailedAuthTable($emailAuthEdc);
        $emailAuthEdc = $this->addIndexToFailedAuthTable($installer, $emailAuthEdc);
        $emailAuthEdc->setComment('Email Failed Auth Table.');
        $installer->getConnection()->createTable($emailAuthEdc);
    }

    /**
     * @param \Magento\Framework\DB\Ddl\Table $table
     * @return mixed
     */
    private function addColumnForFailedAuthTable($table)
    {
        $table
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                [
                    'primary' => true,
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Primary Key'
            )
            ->addColumn(
                'failures_num',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Number of fails'
            )
            ->addColumn(
                'first_attempt_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'First attempt date'
            )
            ->addColumn(
                'last_attempt_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'Last attempt date'
            )
            ->addColumn(
                'url',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => true],
                'URL'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Store Id'
            );

        return $table;
    }

    /**
     * @param SchemaSetupInterface $installer
     * @param \Magento\Framework\DB\Ddl\Table $emailAuthEdc
     * @return mixed
     */
    private function addIndexToFailedAuthTable($installer, $emailAuthEdc)
    {
        return $emailAuthEdc
            ->addIndex(
                $installer->getIdxName('email_auth_edc', ['store_id']),
                ['store_id']
            );
    }
}
