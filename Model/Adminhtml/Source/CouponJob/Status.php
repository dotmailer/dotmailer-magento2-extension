<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\CouponJob;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;

/**
 * Status source for CouponJob
 */
class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => CouponJobInterface::STATUS_PENDING, 'label' => __('Pending')],
            ['value' => CouponJobInterface::STATUS_PROCESSING, 'label' => __('Processing')],
            ['value' => CouponJobInterface::STATUS_COMPLETE, 'label' => __('Complete')],
            ['value' => CouponJobInterface::STATUS_FAILED, 'label' => __('Failed')],
            ['value' => CouponJobInterface::STATUS_CANCELLED, 'label' => __('Cancelled')]
        ];
    }
}
