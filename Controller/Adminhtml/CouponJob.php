<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml;

use Magento\Backend\App\Action;

/**
 * CouponJob Abstract Controller
 */
abstract class CouponJob extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::coupon_job';
}
