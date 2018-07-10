<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

/**
 * Remove wishlist items.
 */
class RemoveWishlistItem implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory
     */
    private $emailWishlistCollection;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist
     */
    private $emailWishlistResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * RemoveWishlistItem constructor.
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $emailWishlistCollection
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $emailWishlistResource
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $emailWishlistCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $emailWishlistResource,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->helper          = $data;
        $this->emailWishlistResource = $emailWishlistResource;
        $this->emailWishlistCollection = $emailWishlistCollection;
    }

    /**
     * Delete wishlist item event.
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $wishlistItem = $observer->getEvent()->getItem();
            $emailWishlist = $this->emailWishlistCollection->create()
                ->getWishlistById($wishlistItem->getWishlistId());

            if ($emailWishlist->getId()) {
                $count = $emailWishlist->getItemCount();
                //update wishlist count and set to modified
                $emailWishlist->setItemCount(--$count);
                $emailWishlist->setWishlistModified(1);
                $this->emailWishlistResource->save($emailWishlist);
            }
        } catch (\Exception $e) {
            $this->helper->log((string)$e, []);
        }
    }
}
