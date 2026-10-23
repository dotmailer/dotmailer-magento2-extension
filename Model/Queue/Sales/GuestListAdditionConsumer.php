<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Sales;

use Dotdigital\V3\Models\ContactFactory as DotdigitalContactFactory;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder;
use Dotdigitalgroup\Email\Model\Queue\Data\GuestListAdditionData;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adds a guest to the guest list of the store their order was placed in.
 *
 * This only adds list membership. It sends no data fields, removes no previous list memberships
 * and writes nothing to email_contact, so the regular guest sync is unaffected.
 * The Dotdigital request is an idempotent upsert, so duplicate or concurrent delivery is safe.
 */
class GuestListAdditionConsumer
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var DotdigitalContactFactory
     */
    private $sdkContactFactory;

    /**
     * @var OptInTypeFinder
     */
    private $optInTypeFinder;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Data $helper
     * @param Logger $logger
     * @param ClientFactory $clientFactory
     * @param DotdigitalContactFactory $sdkContactFactory
     * @param OptInTypeFinder $optInTypeFinder
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        ClientFactory $clientFactory,
        DotdigitalContactFactory $sdkContactFactory,
        OptInTypeFinder $optInTypeFinder,
        ContactCollectionFactory $contactCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->clientFactory = $clientFactory;
        $this->sdkContactFactory = $sdkContactFactory;
        $this->optInTypeFinder = $optInTypeFinder;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Process.
     *
     * Messages that can never succeed are logged and dropped.
     * Unexpected failures (order or contact missing, API errors) throw, which marks the message
     * as errored in the queue. The message is published after the order is committed, so these are not expected.
     *
     * @param GuestListAdditionData $data
     *
     * @return void
     * @throws \Exception
     */
    public function process(GuestListAdditionData $data)
    {
        $websiteId = (int) $data->getWebsiteId();
        $storeId = (int) $data->getStoreId();
        $email = (string) $data->getEmail();
        $orderIncrementId = (string) $data->getOrderIncrementId();

        if (!$this->helper->isEnabled($websiteId) || !$this->helper->isGuestSyncEnabled($websiteId)) {
            $this->skip('Guest sync is not enabled', $data);
            return;
        }

        if ((int) $this->storeManager->getStore($storeId)->getWebsiteId() !== $websiteId) {
            $this->skip('Store does not belong to the website', $data);
            return;
        }

        $listId = (int) $this->helper->getGuestListByStoreId($storeId);
        if (!$listId) {
            $this->skip('No guest list is mapped for the order store', $data);
            return;
        }

        $order = $this->orderCollectionFactory->create()
            ->addFieldToFilter('increment_id', $orderIncrementId)
            ->addFieldToFilter('store_id', $storeId)
            ->setPageSize(1)
            ->getFirstItem();

        if (!$order->getId()) {
            throw new LocalizedException(
                __('Order %1 was not found.', $orderIncrementId)
            );
        }

        if (!$order->getCustomerIsGuest() ||
            strcasecmp((string) $order->getCustomerEmail(), $email) !== 0 ||
            $order->getState() === Order::STATE_CANCELED
        ) {
            $this->skip('Order is not an active guest order for this contact', $data);
            return;
        }

        $contact = $this->contactCollectionFactory->create()
            ->loadByCustomerEmail($email, $websiteId);

        if (!$contact) {
            throw new LocalizedException(__('Contact %1 was not found in email_contact.', $email));
        }

        if ($contact->getCustomerId() || $contact->getSuppressed()) {
            $this->skip('Contact is a registered customer or is suppressed', $data);
            return;
        }

        if ($this->helper->isOnlySubscribersForContactSync($websiteId) &&
            (!$contact->getIsSubscriber() || (int) $contact->getSubscriberStatus() !== Subscriber::STATUS_SUBSCRIBED)
        ) {
            $this->skip('Only subscribed contacts can be imported', $data);
            return;
        }

        $this->addToList($email, $websiteId, $storeId, $listId);

        $this->logger->info(
            'Guest list addition success',
            ['email' => $email, 'order' => $orderIncrementId, 'storeId' => $storeId, 'listId' => $listId]
        );
    }

    /**
     * Add the contact to the list, without data fields.
     *
     * @param string $email
     * @param int $websiteId
     * @param int $storeId
     * @param int $listId
     *
     * @return void
     * @throws \Exception
     */
    private function addToList(string $email, int $websiteId, int $storeId, int $listId)
    {
        $sdkContact = $this->sdkContactFactory->create();
        $sdkContact->setMatchIdentifier('email');
        $sdkContact->setIdentifiers(['email' => $email]);
        $sdkContact->setLists([$listId]);

        $optInType = $this->optInTypeFinder->getOptInType($storeId);
        if ($optInType) {
            $sdkContact->setChannelProperties(['email' => ['optInType' => $optInType]]);
        }

        try {
            $this->clientFactory
                ->create(['data' => ['websiteId' => $websiteId]])
                ->contacts
                ->patchByIdentifier($email, $sdkContact);
        } catch (\Exception $e) {
            $this->logger->error(
                'Guest list addition failed',
                ['email' => $email, 'storeId' => $storeId, 'listId' => $listId, 'exception' => (string) $e]
            );
            throw $e;
        }
    }

    /**
     * Log and drop a message that cannot be processed.
     *
     * @param string $reason
     * @param GuestListAdditionData $data
     *
     * @return void
     */
    private function skip(string $reason, GuestListAdditionData $data)
    {
        $this->logger->info(
            'Guest list addition skipped: ' . $reason,
            [
                'email' => $data->getEmail(),
                'order' => $data->getOrderIncrementId(),
                'storeId' => $data->getStoreId(),
            ]
        );
    }
}
