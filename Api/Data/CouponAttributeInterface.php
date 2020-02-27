<?php

namespace Dotdigitalgroup\Email\Api\Data;

interface CouponAttributeInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    /**
     * @deprecated
     */
    const COUPON_ID = 'coupon_id';

    const SALESRULE_COUPON_ID = 'salesrule_coupon_id';
    const EMAIL = 'email';
    const EXPIRES_AT = 'expires_at';

    /**
     * @return string|null
     */
    public function getEmail();

    /**
     * @param string|null $email
     * @return $this
     */
    public function setEmail($email);

    /**
     * @return \DateTime|null
     */
    public function getExpiresAt();

    /**
     * @param string $expiresAt
     * @return $this
     */
    public function setExpiresAt(string $expiresAt);
}
