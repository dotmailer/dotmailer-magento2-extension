<?php

namespace Dotdigitalgroup\Email\Model\CouponJob\Struct;

use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterface;
use Magento\Framework\DataObject;

class JobConfiguration extends DataObject implements JobConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getValidationPattern(): array
    {
        return static::VALIDATION_PATTERN;
    }
}
