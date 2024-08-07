<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Data;

/**
 * @deprecated Use SubscriptionData as the model for all subscription state queue messages.
 * @see SubscriptionData
 */
class UnsubscriberData
{
    /**
     * @var string|int
     */
    private $id;

    /**
     * @var string
     */
    private $email;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * Set id.
     *
     * This is the row id from email_contact.
     *
     * @param string|int $id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

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
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
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
     * Get website id.
     *
     * Type cast is NOT redundant.
     *
     * @return int
     */
    public function getWebsiteId(): int
    {
        return (int) $this->websiteId;
    }
}
