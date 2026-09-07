<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Stub;

use Dotdigitalgroup\Email\Model\Coupon\CouponAttribute;

/**
 * Stand-in for Magento\SalesRule\Api\Data\CouponExtensionInterface.
 *
 * That interface is generated from extension_attributes.xml at compile time. Unit tests do not
 * read that config, so the code generator produces a marker interface with no methods and
 * getDdgExtensionAttributes() cannot be stubbed on it. This stub declares the accessor the
 * plugin actually calls.
 */
interface CouponExtensionStubInterface
{
    /**
     * Get the Dotdigital coupon attributes.
     *
     * @return CouponAttribute|null
     */
    public function getDdgExtensionAttributes();
}
