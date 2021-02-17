<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Logger\Logger;

/**
 * Remove wishlist items.
 */
class RemoveWishlistItem implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory
     */
    private $emailWishlistCollectionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist
     */
    private $emailWishlistResource;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * RemoveWishlistItem constructor.
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $emailWishlistCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $emailWishlistResource
     * @param Logger $logger
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $emailWishlistCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $emailWishlistResource,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->emailWishlistResource = $emailWishlistResource;
        $this->emailWishlistCollectionFactory = $emailWishlistCollectionFactory;
    }

    /**
     * Delete wishlist item event.
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $wishlistItem = $observer->getEvent()->getItem();
            $emailWishlist = $this->emailWishlistCollectionFactory->create()
                ->getWishlistByIdAndStoreId(
                    $wishlistItem->getWishlistId(),
                    $wishlistItem->getStoreId()
                );

            if ($emailWishlist) {
                $count = $emailWishlist->getItemCount();
                $emailWishlist->setItemCount(--$count);
                $emailWishlist->setWishlistImported(0);
                $this->emailWishlistResource->save($emailWishlist);
            }
        } catch (\Exception $e) {
            $this->logger->debug((string) $e);
        }
    }
}
