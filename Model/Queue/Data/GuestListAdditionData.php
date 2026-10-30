<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Data;

class GuestListAdditionData
{
    /**
     * @var int
     */
    private $websiteId;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var int
     */
    private $contactId;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $orderIncrementId;

    /**
     * Set website id.
     *
     * @param int $websiteId
     *
     * @return void
     */
    public function setWebsiteId($websiteId)
    {
        $this->websiteId = $websiteId;
    }

    /**
     * Set the id of the store the order was placed in.
     *
     * @param int $storeId
     *
     * @return void
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Set the email_contact row id.
     *
     * @param int $contactId
     *
     * @return void
     */
    public function setContactId($contactId)
    {
        $this->contactId = $contactId;
    }

    /**
     * Set contact email.
     *
     * @param string $email
     *
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Set the increment id of the order that triggered the list addition.
     *
     * @param string $orderIncrementId
     *
     * @return void
     */
    public function setOrderIncrementId($orderIncrementId)
    {
        $this->orderIncrementId = $orderIncrementId;
    }

    /**
     * Get website id.
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->websiteId;
    }

    /**
     * Get the id of the store the order was placed in.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Get the email_contact row id.
     *
     * @return int
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    /**
     * Get contact email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get the increment id of the order that triggered the list addition.
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->orderIncrementId;
    }
}
