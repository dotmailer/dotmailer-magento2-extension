<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            //remove quote table
            $connection->dropTable($setup->getTable('email_quote'));
        }
        if (version_compare($context->getVersion(), '2.0.4') < 0) {
            //modify email_campaign table
            $campaignTable = $setup->getTable('email_campaign');

            //add columns
            $connection->addColumn(
                $campaignTable, 'send_id', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Campaign Send Id'
                ]
            );
            $connection->addColumn(
                $campaignTable, 'send_status', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Send Status'
                ]
            );

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
        $setup->endSetup();
    }
}
