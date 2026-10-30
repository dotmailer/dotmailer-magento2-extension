<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Sales;

use Dotdigitalgroup\Email\Model\Queue\Data\GuestListAdditionDataFactory;
use Magento\Framework\MessageQueue\PublisherInterface;

class GuestListAdditionPublisher
{
    public const TOPIC_GUEST_LIST_ADDITION = 'ddg.sales.guest_list_addition';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var GuestListAdditionDataFactory
     */
    private $guestListAdditionDataFactory;

    /**
     * @param PublisherInterface $publisher
     * @param GuestListAdditionDataFactory $guestListAdditionDataFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        GuestListAdditionDataFactory $guestListAdditionDataFactory
    ) {
        $this->publisher = $publisher;
        $this->guestListAdditionDataFactory = $guestListAdditionDataFactory;
    }

    /**
     * Publish a request to add a guest to the guest list of the store their order was placed in.
     *
     * @param int $websiteId
     * @param int $storeId
     * @param int $contactId
     * @param string $email
     * @param string $orderIncrementId
     *
     * @return void
     */
    public function publish(
        int $websiteId,
        int $storeId,
        int $contactId,
        string $email,
        string $orderIncrementId
    ): void {
        $data = $this->guestListAdditionDataFactory->create();
        $data->setWebsiteId($websiteId);
        $data->setStoreId($storeId);
        $data->setContactId($contactId);
        $data->setEmail($email);
        $data->setOrderIncrementId($orderIncrementId);

        $this->publisher->publish(self::TOPIC_GUEST_LIST_ADDITION, $data);
    }
}
