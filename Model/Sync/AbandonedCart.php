<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\EnrollerFactory;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory;
use Dotdigitalgroup\Email\Model\Sync\SyncTimeService;

/**
 * Sync abandoned carts
 */
class AbandonedCart implements SyncInterface
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var EnrollerFactory
     */
    private $enrollerFactory;

    /**
     * @var SyncTimeService
     */
    private $syncTimeService;

    /**
     * AbandonedCart constructor
     *
     * @param QuoteFactory $quoteFactory
     * @param EnrollerFactory $enrollerFactory
     * @param \Dotdigitalgroup\Email\Model\Sync\SyncTimeService $syncTimeService
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        EnrollerFactory $enrollerFactory,
        SyncTimeService $syncTimeService
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->enrollerFactory = $enrollerFactory;
        $this->syncTimeService = $syncTimeService;
    }

    /*
     * @inheritdoc
     */
    public function sync(\DateTime $from = null)
    {
        $this->syncTimeService->setSyncFromTime($from);

        // normal abandoned carts
        $this->quoteFactory->create()
            ->processAbandonedCarts();

        // enrolment abandoned carts
        $this->enrollerFactory->create()
            ->process();
    }
}
