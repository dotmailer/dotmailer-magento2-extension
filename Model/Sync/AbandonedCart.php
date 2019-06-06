<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Model\Sales\QuoteFactory;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\EnrollerFactory;

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
     * AbandonedCart constructor
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        EnrollerFactory $enrollerFactory
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->enrollerFactory = $enrollerFactory;
    }

    /*
     * @inheritdoc
     */
    public function sync(\DateTime $from = null)
    {
        // normal abandoned carts
        $this->quoteFactory->create()
            ->setSyncFromTime($from)
            ->processAbandonedCarts();

        // enrolment abandoned carts
        $this->enrollerFactory->create()
            ->setSyncFromTime($from)
            ->process();
    }
}