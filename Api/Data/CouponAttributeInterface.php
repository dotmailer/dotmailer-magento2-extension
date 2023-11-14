<?php

namespace Dotdigitalgroup\Email\Api\Data;

interface CouponAttributeInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    /**
     * @deprecated 4.3.6 Use SALESRULE_COUPON_ID instead.
     * @see CouponAttributeInterface::SALESRULE_COUPON_ID
     */
    public const COUPON_ID = 'coupon_id';

    public const SALESRULE_COUPON_ID = 'salesrule_coupon_id';
    public const EMAIL = 'email';
    public const EXPIRES_AT = 'expires_at';

    /**
     * Coupon attribute email.
     *
     * @return string|null
     */
    public function getEmail();

    /**
     * Set coupon attribute email.
     *
     * @param string|null $email
     * @return $this
     */
    public function setEmail($email);

    /**
     * Coupon attribute expires at.
     *
     * @return string|null
     */
    public function getExpiresAt();

    /**
     * Set coupon attribute expires at.
     *
     * @param string $expiresAt
     * @return $this
     */
    public function setExpiresAt(string $expiresAt);
}
