<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails;

use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Magento\Framework\DataObject;

/**
 * Report Data Model
 */
class Report extends DataObject implements ReportInterface
{
    /**
     * @inheritDoc
     */
    public function getValidationPattern(): array
    {
        return static::VALIDATION_PATTERN;
    }
}
