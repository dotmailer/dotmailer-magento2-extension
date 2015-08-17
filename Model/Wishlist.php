<?php

namespace Dotdigitalgroup\Email\Model;

class Wishlist extends \Magento\Framework\Model\AbstractModel
{
    private $_start;
    private $_wishlists;
    private $_count = 0;
    private $_wishlistIds;

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Dotdigitalgroup\Email\Model\Resource\Wishlist');
    }

    /**
     * @param int $wishListId
     */
    public function getWishlist($wishListId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('wishlist_id', $wishListId)
            ->setPageSize(1);

        if ($collection->count()) {
            return $collection->getFirstItem();
        }
        return false;
    }


}