<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails;

use Dotdigitalgroup\Email\Model\Validator\ValidatableStructInterface;

/**
 * Report Interface
 *
 * Represents job processing report statistics
 *
 * @api
 */
interface ReportInterface extends ValidatableStructInterface
{
    public const TOTAL_RECORDS = 'total_records';
    public const TOTAL_RECORDS_IMPORTED = 'total_records_imported';
    public const TOTAL_BATCHES = 'total_batches';
    public const TOTAL_BATCHES_PROCESSED = 'total_batches_processed';
    public const BATCH_RECORDS_IMPORTED = 'batch_records_imported';

    public const VALIDATION_PATTERN = [
        self::TOTAL_RECORDS => 'required|isInt',
        self::TOTAL_RECORDS_IMPORTED => 'required|isInt',
        self::TOTAL_BATCHES => 'required|isInt',
        self::TOTAL_BATCHES_PROCESSED => 'required|isInt',
    ];
}
