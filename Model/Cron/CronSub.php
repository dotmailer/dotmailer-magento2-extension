<?php

namespace Dotdigitalgroup\Email\Model\Cron;

use Magento\Framework\Exception\LocalizedException;

class CronSub
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\ReviewFactory
     */
    private $reviewFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\WishlistFactory
     */
    private $wishlistFactory;

    /**
     * CronSub constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Sync\ReviewFactory $reviewFactory
     * @param \Dotdigitalgroup\Email\Model\Sync\WishlistFactory $wishlistFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Sync\ReviewFactory $reviewFactory,
        \Dotdigitalgroup\Email\Model\Sync\WishlistFactory $wishlistFactory
    ) {
        $this->wishlistFactory   = $wishlistFactory;
        $this->reviewFactory     = $reviewFactory;
    }

    /**
     * Review sync.
     *
     * @return array
     * @throws LocalizedException
     */
    public function reviewSync()
    {
        $result = $this->reviewFactory->create()
            ->sync();

        return $result;
    }

    /**
     * Wishlist sync
     */
    public function wishlistSync()
    {
        $this->wishlistFactory->create()
            ->sync();
    }
}
