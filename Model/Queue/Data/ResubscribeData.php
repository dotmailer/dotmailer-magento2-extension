<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Data;

/**
 * @deprecated Use SubscriptionData as the model for all subscription state queue messages.
 * @see SubscriptionData
 */
class ResubscribeData
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return void
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set website id.
     *
     * @param string|int $websiteId
     *
     * @return void
     */
    public function setWebsiteId($websiteId)
    {
        (int) $this->websiteId = $websiteId;
    }

    /**
     * Get website id.
     *
     * Type cast is NOT redundant.
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return (int) $this->websiteId;
    }
}
