<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Data;

class EmailUpdateData
{
    /**
     * @var string
     */
    private $emailBefore;

    /**
     * @var string
     */
    private $email;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * Set email before.
     *
     * @param string $emailBefore
     *
     * @return void
     */
    public function setEmailBefore(string $emailBefore): void
    {
        $this->emailBefore = $emailBefore;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return void
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Set website id.
     *
     * @param int $websiteId
     *
     * @return void
     */
    public function setWebsiteId(int $websiteId): void
    {
        $this->websiteId = $websiteId;
    }

    /**
     * Get email before.
     *
     * @return string
     */
    public function getEmailBefore(): string
    {
        return $this->emailBefore;
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
     * @return int
     */
    public function getWebsiteId(): int
    {
        return $this->websiteId;
    }
}
