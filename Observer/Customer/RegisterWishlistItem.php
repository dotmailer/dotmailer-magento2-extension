<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

class RegisterWishlistItem implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Dotdigitalgroup\Email\Model\WishlistFactory
     */
    public $wishlistFactory;
    /**
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    public $wishlist;

    /**
     * RegisterWishlistItem constructor.
     *
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlist
     * @param \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Magento\Wishlist\Model\WishlistFactory $wishlist,
        \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->wishlist        = $wishlist;
        $this->wishlistFactory = $wishlistFactory;
        $this->helper          = $data;
    }

    /**
     * If it's configured to capture on shipment - do this.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $object = $observer->getEvent()->getDataObject();
        $wishlist = $this->wishlist->create()
            ->load($object->getWishlistId());
        $emailWishlist = $this->wishlistFactory->create();
        try {
            if ($object->getWishlistId()) {
                $itemCount = count($wishlist->getItemCollection());
                $item
                           = $emailWishlist->getWishlist($object->getWishlistId());

                if ($item && $item->getId()) {
                    $preSaveItemCount = $item->getItemCount();

                    if ($itemCount != $item->getItemCount()) {
                        $item->setItemCount($itemCount);
                    }

                    if ($itemCount == 1 && $preSaveItemCount == 0) {
                        $item->setWishlistImported(null);
                    } elseif ($item->getWishlistImported()) {
                        $item->setWishlistModified(1);
                    }

                    $item->save();
                }
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }
}
