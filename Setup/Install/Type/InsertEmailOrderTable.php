<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;

class InsertEmailOrderTable extends AbstractBatchInserter implements InsertTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = Schema::EMAIL_ORDER_TABLE;

    /**
     * @var string
     */
    protected $resourceName = 'sales';

    /**
     * @var CollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * InsertEmailOrderTable constructor
     *
     * @param CollectionFactory $contactCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CollectionFactory $contactCollectionFactory,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($resourceConnection, $scopeConfig);
    }

    /**
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->resourceConnection
            ->getConnection($this->resourceName)
            ->select()
            ->from([
                $this->resourceConnection->getTableName('sales_order', $this->resourceName),
            ], [
                'order_id' => 'entity_id',
                'quote_id',
                'store_id',
                'created_at',
                'updated_at',
                'order_status' => 'status',
                'is_guest' => new \Zend_Db_Expr('1'),
                'customer_id',
                'customer_email',
            ])
            ->where('customer_email is ?', new \Zend_Db_Expr('not null'))
            ->where('customer_email != ?', '')
            ->order('order_id');
    }

    /**
     * @inheritdoc
     */
    public function getInsertArray()
    {
        return [
            'order_id',
            'quote_id',
            'store_id',
            'created_at',
            'updated_at',
            'order_status',
        ];
    }

    /**
     * For email_order, we must retrieve records first using the 'sales' connection.
     * Fetched records are then inserted into the target db/table as an array.
     * This alternate approach is required to support split databases.
     *
     * @param Select $selectStatement
     * @return int
     */
    protected function insertData(Select $selectStatement)
    {
        $fetched = $this->resourceConnection->getConnection($this->resourceName)
            ->fetchAll($selectStatement);

        if (empty($fetched)) {
            return 0;
        }

        $this->insertGuestsFromArray($fetched);
        $this->deleteUnusedCols($fetched);

        return $this->resourceConnection
            ->getConnection()
            ->insertArray(
                $this->resourceConnection->getTableName($this->tableName),
                $this->getInsertArray(),
                $fetched
            );
    }

    /**
     * Inserts guest contacts in the order data array into email_contact.
     *
     * This step added here as a faster method than a standalone insert query.
     *
     * @param array $orderData
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function insertGuestsFromArray(array $orderData)
    {
        $this->removeRowsWithCustomerId($orderData);
        $storeGroups = $this->groupOrderDataByStore($orderData);

        foreach ($storeGroups as $storeId => $storeOrders) {
            $guestsToInsert = [];
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            $guestOrderEmails = array_unique(
                array_column(
                    $storeOrders,
                    'customer_email'
                )
            );
            $matchingContactEmails = $this->contactCollectionFactory->create()
                ->matchEmailsToContacts(
                    $guestOrderEmails,
                    $websiteId
                );

            foreach (array_udiff($guestOrderEmails, $matchingContactEmails, 'strcasecmp') as $email) {
                $guestsToInsert[] = [
                    'is_guest' => '1',
                    'email' => $email,
                    'store_id' => (string) $storeId,
                    'website_id' => $websiteId,
                ];
            }

            if (empty($guestsToInsert)) {
                continue;
            }

            $this->resourceConnection
                ->getConnection()
                ->insertArray(
                    $this->resourceConnection->getTableName(Schema::EMAIL_CONTACT_TABLE),
                    [
                        'is_guest',
                        'email',
                        'store_id',
                        'website_id'
                    ],
                    $guestsToInsert
                );
        }
    }

    /**
     * Filter the data for rows where customer_id is null.
     *
     * @param array $data
     *
     * @return void
     */
    private function removeRowsWithCustomerId(&$data)
    {
        $data = array_filter($data, function ($row) {
            return empty($row['customer_id']);
        });
    }

    /**
     * Organise order data into a new array keyed on store_id.
     *
     * @param array $orderData
     *
     * @return array
     */
    private function groupOrderDataByStore(array $orderData)
    {
        $storeData = [];
        foreach ($orderData as $row) {
            $storeData[$row['store_id']][] = $row;
        }
        return $storeData;
    }

    /**
     * Remove the columns we needed for the guest insert, prior to the email_order insert.
     *
     * @param array $array
     *
     * @return void
     */
    private function deleteUnusedCols(&$array)
    {
        foreach ($array as &$item) {
            $item = array_slice($item, 0, 6);
        }
    }
}
