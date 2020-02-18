<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Wishlist;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Wishlist::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist::class
        );
    }

    /**
     * Join the customer email and store id.
     * @return \Magento\Framework\DB\Select
     */
    public function joinLeftCustomer()
    {
        return $this->getSelect()
            ->joinLeft([
                'c' => $this->_resource->getTable('customer_entity')
            ], 'c.entity_id = customer_id', ['email', 'store_id']);
    }

    /**
     * Get the collection first item.
     *
     * @param int $wishListId
     *
     * @return bool|\Magento\Framework\DataObject
     */
    public function getWishlistById($wishListId)
    {
        $collection = $this->addFieldToFilter('wishlist_id', $wishListId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param int                                      $limit
     *
     * @return $this
     */
    public function getWishlistToImportByWebsite(\Magento\Store\Api\Data\WebsiteInterface $website, $limit = 100)
    {
        $collection = $this->addFieldToFilter('wishlist_imported', 0)
            ->addFieldToFilter(
                'store_id',
                ['in' => $website->getStoreIds()]
            )
            ->addFieldToFilter('item_count', ['gt' => 0]);
        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * Get wishlists marked as modified for website.
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param int $limit
     * @return $this
     */
    public function getModifiedWishlistToImportByWebsite(
        \Magento\Store\Api\Data\WebsiteInterface $website,
        $limit = 100
    ) {

        $collection = $this->addFieldToFilter('wishlist_modified', 1)
            ->addFieldToFilter(
                'store_id',
                ['in' => $website->getStoreIds()]
            );
        $collection->getSelect()->limit($limit);

        return $collection;
    }
}
