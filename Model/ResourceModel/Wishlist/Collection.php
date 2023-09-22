<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Wishlist;

use Magento\Store\Model\Website;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Wishlist::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist::class
        );
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
     * Get the collection first item.
     *
     * @param int $wishListId
     * @param string|int $storeId
     * @return bool|\Magento\Framework\DataObject
     */
    public function getWishlistByIdAndStoreId($wishListId, $storeId)
    {
        $collection = $this->addFieldToFilter('wishlist_id', $wishListId)
            ->addFieldToFilter('store_id', $storeId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Get wishlists to import by website.
     *
     * @param Website $website
     * @param int $limit
     *
     * @return $this
     */
    public function getWishlistsToImportByWebsite(Website $website, $limit = 100)
    {
        $collection = $this->addFieldToFilter('wishlist_imported', 0)
            ->addFieldToFilter(
                'store_id',
                ['in' => $website->getStoreIds()]
            );
        $collection->getSelect()->limit($limit);

        return $collection;
    }
}
