<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;

class SingleSubscriberSyncer
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var DataFieldCollector
     */
    private $dataFieldCollector;

    /**
     * @var SubscriberListResolver
     */
    private $subscriberListResolver;

    /**
     * @param Data $helper
     * @param ClientFactory $clientFactory
     * @param ContactResource $contactResource
     * @param DataFieldCollector $dataFieldCollector
     * @param SubscriberListResolver $subscriberListResolver
     */
    public function __construct(
        Data $helper,
        ClientFactory $clientFactory,
        ContactResource $contactResource,
        DataFieldCollector $dataFieldCollector,
        SubscriberListResolver $subscriberListResolver
    ) {
        $this->helper = $helper;
        $this->clientFactory = $clientFactory;
        $this->contactResource = $contactResource;
        $this->dataFieldCollector = $dataFieldCollector;
        $this->subscriberListResolver = $subscriberListResolver;
    }

    /**
     * Execute the sync process for a single subscriber.
     *
     * @param Contact $contact
     * @param int|null $storeId Subscriber store. Defaults to the contact's store.
     *
     * @return void
     * @throws LocalizedException|\Http\Client\Exception
     */
    public function execute(Contact $contact, ?int $storeId = null): void
    {
        $response = $this->pushContactToSubscriberAddressBook($contact, $storeId);
        $this->markProcessedSubscriberAsImported($contact, $response);
    }

    /**
     * Add subscriber to subscriber list
     *
     * @param Contact $contact
     * @param int|null $storeId Subscriber store. Defaults to the contact's store.
     *
     * @return SdkContact|null
     * @throws LocalizedException|\Http\Client\Exception
     *
     * @deprecated This method will be marked as private in the next major release.
     * @see SingleSubscriberSyncer::pushContactToSubscriberAddressBook()
     */
    public function pushContactToSubscriberAddressBook(Contact $contact, ?int $storeId = null): ?SdkContact
    {
        $websiteId = (int) $contact->getWebsiteId();
        if (!$this->helper->isSubscriberSyncEnabled($websiteId)) {
            return null;
        }

        $storeId = $this->subscriberListResolver->resolveStoreId(
            $websiteId,
            $storeId,
            (int) $contact->getStoreId()
        );
        $subscriberAddressBookId = $this->subscriberListResolver->getListId($websiteId, $storeId);
        if (!$subscriberAddressBookId) {
            return null;
        }

        $sdkSubscriber = $this->dataFieldCollector->collectForSubscriber(
            $contact,
            $websiteId,
            $subscriberAddressBookId
        );

        if (!$sdkSubscriber) {
            return null;
        }

        return $this->clientFactory
            ->create(['data' => ['websiteId' => $websiteId]])
            ->contacts
            ->patchByIdentifier(
                $contact->getEmail(),
                $sdkSubscriber
            );
    }

    /**
     * Mark contact as imported.
     *
     * @param Contact $contact
     * @param SdkContact|null $response
     *
     * @return void
     * @throws AlreadyExistsException
     */
    private function markProcessedSubscriberAsImported(Contact $contact, ?SdkContact $response): void
    {
        if ($response) {
            $contact->setContactId($response->getContactId());
            $contact->setSubscriberImported(1);
            $this->contactResource->save($contact);
        }
    }
}
