<?php

namespace Dotdigitalgroup\Email\Api\Data;

interface CouponAttributeInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    const EMAIL = 'email';

    const COUPON_ID = 'coupon_id';

    /**
     * Return value.
     *
     * @return string|null
     */
    public function getEmail();

    /**
     * Set value.
     *
     * @param string|null $value
     * @return $this
     */
    public function setEmail($email);
}
