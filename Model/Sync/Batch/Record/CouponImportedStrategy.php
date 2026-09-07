<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Record;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Record\RecordImportedStrategyInterface;

/**
 * Generic strategy for coupon job imports.
 *
 * Coupon contacts already exist in Dotdigital. There are no local Magento records
 * to mark as "imported" after a batch completes. Progress tracking is handled
 * exclusively through the CouponJob model.
 */
class CouponImportedStrategy implements RecordImportedStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function setRecords(array $records): RecordImportedStrategyInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     *
     * Intentionally empty — no local records need updating.
     */
    public function process(): void // phpcs:ignore
    {
    }
}
