<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    private $config;

    /**
     * @var \Magento\Catalog\Model\Product\TypeFactory
     */
    private $typefactory;

    /**
     * @var \Magento\Catalog\Model\Product\VisibilityFactory
     */
    private $visibilityFactory;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    private $orderConfigFactory;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $randomMath;

    /**
     * InstallData constructor.
     *
     * @param \Magento\Config\Model\ResourceModel\Config $config
     * @param \Magento\Catalog\Model\Product\TypeFactory $typeFactory
     * @param \Magento\Catalog\Model\Product\VisibilityFactory $visibilityFactory
     * @param \Magento\Sales\Model\Order\Config $orderConfigFactory
     * @param \Magento\Framework\Math\Random $random
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $config,
        \Magento\Catalog\Model\Product\TypeFactory $typeFactory,
        \Magento\Catalog\Model\Product\VisibilityFactory $visibilityFactory,
        \Magento\Sales\Model\Order\Config $orderConfigFactory,
        \Magento\Framework\Math\Random $random
    ) {
        $this->config = $config;
        $this->typefactory = $typeFactory;
        $this->visibilityFactory = $visibilityFactory;
        $this->orderConfigFactory = $orderConfigFactory;
        $this->randomMath = $random;
    }

    /**
     * {@inheritdoc}
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Populate table
         */
        $this->populateEmailContactTable($installer);
        $this->updateContactsWithCustomersThatAreSubscribers($installer);
        $this->populateEmailOrderTable($installer);
        $this->populateEmailReviewTable($installer);
        $this->populateEmailWishlistTable($installer);
        $this->populateEmailCatalogTable($installer);

        /**
         * Save config value
         */
        $this->saveAllOrderStatusesAsString($this->config);
        $this->saveAllProductTypesAsString($this->config);
        $this->saveAllProductVisibilitiesAsString($this->config);
        $this->generateAndSaveCode();

        $installer->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $installer
     *
     * @return null
     */
    private function populateEmailContactTable($installer)
    {
        $select = $installer->getConnection()->select()
            ->from(
                [
                    'customer' => $installer->getTable('customer_entity')
                ],
                [
                    'customer_id' => 'entity_id',
                    'email',
                    'website_id',
                    'store_id'
                ]
            );

        $insertArray = ['customer_id', 'email', 'website_id', 'store_id'];
        $sqlQuery = $select->insertFromSelect(
            $installer->getTable(Schema::EMAIL_CONTACT_TABLE),
            $insertArray,
            false
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
            $installer->getTable(Schema::EMAIL_CONTACT_TABLE),
            $insertArray,
            false
        );
        $installer->getConnection()->query($sqlQuery);
    }

    /**
     * @param ModuleDataSetupInterface $installer
     *
     * @return null
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
            $installer->getConnection()->update(
                $installer->getTable(Schema::EMAIL_CONTACT_TABLE),
                [
                    'is_subscriber' => new \Zend_Db_Expr('1'),
                    'subscriber_status' => new \Zend_Db_Expr('1')
                ],
                ["customer_id in (?)" => $customerIds]
            );
        }
    }

    /**
     * @param ModuleDataSetupInterface $installer
     *
     * @return null
     */
    private function populateEmailOrderTable($installer)
    {
        $dotmailerTableConnection = $installer->getConnection();
        $dotmailerTableName = $installer->getTable(Schema::EMAIL_ORDER_TABLE);
        $dotmailerTableTargetColumns = [
                                                'order_id',
                                                'quote_id',
                                                'store_id',
                                                'created_at',
                                                'updated_at',
                                                'order_status'
                                              ];
        $magentoTableConnection = $installer->getConnection('sales');
        $magentoTableName = $installer->getTable('sales_order', 'sales');
        $magentoTableIdColumn = 'entity_id';
        $magentoTableSourceColumns = [
                                                'order_id' => 'entity_id',
                                                'quote_id',
                                                'store_id',
                                                'created_at',
                                                'updated_at',
                                                'order_status' => 'status'
                                            ];
        $batchSize = 1000;

        $this->batchPopulateTable(
            $dotmailerTableConnection,
            $dotmailerTableName,
            $dotmailerTableTargetColumns,
            $magentoTableConnection,
            $magentoTableName,
            $magentoTableIdColumn,
            $magentoTableSourceColumns,
            $batchSize
        );
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $dotmailerTableConnection
     * @param string $dotmailerTableName
     * @param array $dotmailerTableTargetColumns
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $magentoTableConnection
     * @param string $magentoTableName
     * @param string $magentoTableIdColumn
     * @param array $magentoTableSourceColumns
     * @param int $batchSize
     *
     * @return null
     */
    private function batchPopulateTable(
        $dotmailerTableConnection,
        $dotmailerTableName,
        $dotmailerTableTargetColumns,
        $magentoTableConnection,
        $magentoTableName,
        $magentoTableIdColumn,
        $magentoTableSourceColumns,
        $batchSize
    ) {
        $sourceConnection = $magentoTableConnection;
        $sourceTableName = $magentoTableName;
        $sourceTableIdColumn = $magentoTableIdColumn;
        $sourceTableColumns = $magentoTableSourceColumns;

        $minSourceIdSelect = $sourceConnection->select()
                                              ->from($sourceTableName, [$sourceTableIdColumn])
                                              ->order("$sourceTableIdColumn ASC");

        $minSourceId = $sourceConnection->fetchRow($minSourceIdSelect)[$sourceTableIdColumn];
        if ($minSourceId) {
            $maxSourceIdSelect = $sourceConnection->select()
                                                  ->from($sourceTableName, [$sourceTableIdColumn])
                                                  ->order("$sourceTableIdColumn DESC");

            $maxOrderId = $sourceConnection->fetchRow($maxSourceIdSelect)[$sourceTableIdColumn];

            $batchMinId = $minSourceId;
            $batchMaxId = $minSourceId + $batchSize;
            $moreRecords = true;

            while ($moreRecords) {
                $sourceBatchSelect = $sourceConnection->select()
                                                      ->from($sourceTableName, $sourceTableColumns)
                                                      ->where('entity_id >= ?', $batchMinId)
                                                      ->where('entity_id < ?', $batchMaxId);

                $pageOfResults = $sourceConnection->fetchAll($sourceBatchSelect);

                $dotmailerTableConnection->insertArray(
                    $dotmailerTableName,
                    $dotmailerTableTargetColumns,
                    $pageOfResults
                );

                $moreRecords = $maxOrderId >= $batchMaxId;
                $batchMinId = $batchMinId + $batchSize;
                $batchMaxId = $batchMaxId + $batchSize;
            }
        }
    }

    /**
     * @param ModuleDataSetupInterface $installer
     *
     * @return null
     */
    private function populateEmailReviewTable($installer)
    {
        $inCond = $installer->getConnection()->prepareSqlCondition(
            'review_detail.customer_id',
            ['notnull' => true]
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
            $installer->getTable(Schema::EMAIL_REVIEW_TABLE),
            $insertArray,
            false
        );
        $installer->getConnection()->query($sqlQuery);
    }

    /**
     * @param ModuleDataSetupInterface $installer
     *
     * @return null
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
            $installer->getTable(Schema::EMAIL_WISHLIST_TABLE),
            $insertArray,
            false
        );
        $installer->getConnection()->query($sqlQuery);
    }

    /**
     * @param ModuleDataSetupInterface $installer
     *
     * @return null
     */
    private function populateEmailCatalogTable($installer)
    {
        $emailCatalogTable = $installer->getTable(Schema::EMAIL_CATALOG_TABLE);
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
            )
            ->where(
                'catalog.entity_id NOT IN (?)',
                $installer->getConnection()->select()->from($emailCatalogTable, ['id'])
            );
        $insertArray = ['product_id', 'created_at'];
        $sqlQuery = $select->insertFromSelect(
            $emailCatalogTable,
            $insertArray,
            false
        );
        $installer->getConnection()->query($sqlQuery);
    }

    /**
     * @param \Magento\Config\Model\ResourceModel\Config $configModel
     *
     * @return null
     */
    private function saveAllOrderStatusesAsString($configModel)
    {
        $options = array_keys($this->orderConfigFactory->getStatuses());
        $statusString = implode(',', $options);
        $configModel->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS,
            $statusString,
            'website',
            0
        );
    }

    /**
     * @param \Magento\Config\Model\ResourceModel\Config $configModel
     * @return null
     */
    private function saveAllProductTypesAsString($configModel)
    {
        $types = $this->typefactory
            ->create()
            ->toOptionArray();
        $options = [];
        foreach ($types as $type) {
            $options[] = $type['value'];
        }
        $typeString = implode(',', $options);
        $configModel->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE,
            $typeString,
            'website',
            '0'
        );
    }

    /**
     * @param \Magento\Config\Model\ResourceModel\Config $configModel
     *
     * @return null
     */
    private function saveAllProductVisibilitiesAsString($configModel)
    {
        $visibilities = $this->visibilityFactory
            ->create()
            ->toOptionArray();
        $options = [];
        foreach ($visibilities as $visibility) {
            $options[] = $visibility['value'];
        }
        $visibilityString = implode(',', $options);
        $configModel->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY,
            $visibilityString,
            'website',
            '0'
        );
    }

    /**
     * Generate and save code
     */
    private function generateAndSaveCode()
    {
        $code = $this->randomMath->getRandomString(32);
        $this->config->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE,
            $code,
            'default',
            '0'
        );
    }
}
