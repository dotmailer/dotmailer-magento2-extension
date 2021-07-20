<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory;

class Wishlist extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var CollectionFactory
     */
    private $coreWishlistCollectionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_WISHLIST_TABLE, 'id');
    }

    /**
     * Wishlist constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param CollectionFactory $coreWishlistCollectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        CollectionFactory $coreWishlistCollectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->coreWishlistCollectionFactory = $coreWishlistCollectionFactory;
        $this->helper = $data;
        parent::__construct($context);
    }

    /**
     * Reset the email wishlist for re-import.
     *
     * @param string|null $from
     * @param string|null $to
     *
     * @return int
     *
     */
    public function resetWishlists($from = null, $to = null)
    {
        $conn = $this->getConnection();
        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'wishlist_imported = ?' => 1
            ];
        } else {
            $where = ['wishlist_imported = ?' => 1];
        }
        $num = $conn->update(
            $this->getTable(Schema::EMAIL_WISHLIST_TABLE),
            [
                'wishlist_imported' => 0
            ],
            $where
        );

        return $num;
    }

    /**
     * @param int $customerId
     *
     * @return bool|\Magento\Framework\DataObject
     */
    public function getWishlistsForCustomer($customerId)
    {
        if ($customerId) {
            $collection = $this->coreWishlistCollectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->setOrder('updated_at', 'DESC')
                ->setPageSize(1);

            if ($collection->getSize()) {
                return $collection->getFirstItem();
            }
        }

        return false;
    }

    /**
     * @param array $ids
     * @return \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection
     */
    public function getMagentoWishlistsByIds($ids)
    {
        $collection = $this->coreWishlistCollectionFactory->create()
            ->addFieldToFilter('main_table.wishlist_id', ['in' => $ids])
            ->addFieldToFilter('customer_id', ['notnull' => 'true']);

        $collection->getSelect()
            ->joinLeft(
                ['c' => $this->getTable('customer_entity')],
                'c.entity_id = customer_id',
                ['email']
            );

        return $collection;
    }

    /**
     * @param array $ids
     */
    public function setImported($ids)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $this->getTable(Schema::EMAIL_WISHLIST_TABLE);

            $coreResource->update(
                $tableName,
                ['wishlist_imported' => 1],
                ["id IN (?)" => $ids]
            );
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
