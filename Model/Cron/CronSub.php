<?php

namespace Dotdigitalgroup\Email\Model\Cron;

class CronSub
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Sales\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\ReviewFactory
     */
    private $reviewFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\Wishlist
     */
    private $wishlistFactory;

    /**
     * CronSub constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Sync\ReviewFactory $reviewFactory
     * @param \Dotdigitalgroup\Email\Model\Sales\OrderFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Model\Sync\WishlistFactory $wishlistFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Sync\ReviewFactory $reviewFactory,
        \Dotdigitalgroup\Email\Model\Sales\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Model\Sync\WishlistFactory $wishlistFactory
    ) {
        $this->wishlistFactory   = $wishlistFactory;
        $this->reviewFactory     = $reviewFactory;
        $this->orderFactory      = $orderFactory;
    }

    /**
     * Review sync.
     *
     * @return array
     */
    public function reviewSync()
    {
        //find orders to review and register campaign
        $this->orderFactory->create()
            ->createReviewCampaigns();
        //sync reviews
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
