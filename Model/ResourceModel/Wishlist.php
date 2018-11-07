<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\Schema;

class Wishlist extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    public $wishlist;
    
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
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlist
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Wishlist\Model\WishlistFactory $wishlist,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->wishlist = $wishlist;
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
                'wishlist_imported is ?' => new \Zend_Db_Expr('not null')
            ];
        } else {
            $where = $conn->quoteInto(
                'wishlist_imported is ?',
                new \Zend_Db_Expr('not null')
            );
        }
        $num = $conn->update(
            $this->getTable(Schema::EMAIL_WISHLIST_TABLE),
            [
                'wishlist_imported' => new \Zend_Db_Expr('null'),
                'wishlist_modified' => new \Zend_Db_Expr('null'),
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
            $collection = $this->wishlist->create()
                ->getCollection()
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
     *
     * @return mixed
     */
    public function getWishlistByIds($ids)
    {
        $collection = $this->wishlist->create()
            ->getCollection()
            ->addFieldToFilter('main_table.wishlist_id', ['in' => $ids])
            ->addFieldToFilter('customer_id', ['notnull' => 'true']);

        $collection->getSelect()
            ->joinLeft(
                ['c' => $this->getTable('customer_entity')],
                'c.entity_id = customer_id',
                ['email', 'store_id']
            );

        return $collection;
    }

    /**
     * @param array $ids
     * @param string $updatedAt
     * @param bool $modified
     *
     * @return null
     */
    public function setImported($ids, $updatedAt, $modified = false)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $this->getTable(Schema::EMAIL_WISHLIST_TABLE);

            //mark imported modified wishlists
            if ($modified) {
                $coreResource->update(
                    $tableName,
                    [
                        'wishlist_modified' => 'null',
                        'updated_at' => $updatedAt,
                    ],
                    ["wishlist_id IN (?)" => $ids]
                );
            } else {
                $coreResource->update(
                    $tableName,
                    ['wishlist_imported' => 1, 'updated_at' => $updatedAt],
                    ["wishlist_id IN (?)" => $ids]
                );
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
