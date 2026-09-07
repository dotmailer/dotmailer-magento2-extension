<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob\Contracts;

/**
 * Interface for serialising concurrent writes to a CouponJob's job_details blob via a row lock.
 *
 * Allows integrators to swap implementations via di.xml preference
 * without patching core code.
 */
interface JobUpdaterInterface
{
    /**
     * Load the CouponJob under a FOR UPDATE lock, apply $mutator, then save.
     *
     * @param int $couponJobId
     * @param callable $mutator function(CouponJob $couponJob): void
     * @return bool True when the job was found, mutated and saved.
     */
    public function update(int $couponJobId, callable $mutator): bool;
}
