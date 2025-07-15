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
     * @param Data $helper
     * @param ClientFactory $clientFactory
     * @param ContactResource $contactResource
     * @param DataFieldCollector $dataFieldCollector
     */
    public function __construct(
        Data $helper,
        ClientFactory $clientFactory,
        ContactResource $contactResource,
        DataFieldCollector $dataFieldCollector
    ) {
        $this->helper = $helper;
        $this->clientFactory = $clientFactory;
        $this->contactResource = $contactResource;
        $this->dataFieldCollector = $dataFieldCollector;
    }

    /**
     * Execute the sync process for a single subscriber.
     *
     * @param Contact $contact
     *
     * @return void
     * @throws LocalizedException|\Http\Client\Exception
     */
    public function execute(Contact $contact): void
    {
        $response = $this->pushContactToSubscriberAddressBook($contact);
        $this->markProcessedSubscriberAsImported($contact, $response);
    }

    /**
     * Add subscriber to subscriber list
     *
     * @param Contact $contact
     *
     * @return SdkContact|null
     * @throws LocalizedException|\Http\Client\Exception
     *
     * @deprecated This method will be marked as private in the next major release.
     * @see SingleSubscriberSyncer::pushContactToSubscriberAddressBook()
     */
    public function pushContactToSubscriberAddressBook(Contact $contact): ?SdkContact
    {
        $websiteId = (int) $contact->getWebsiteId();
        $subscriberSyncEnabled = $this->helper->isSubscriberSyncEnabled($websiteId);
        $subscriberAddressBookId = $this->helper->getSubscriberAddressBook($websiteId);
        $optInType = null;

        if (!$subscriberSyncEnabled || !$subscriberAddressBookId) {
            return null;
        }

        $sdkSubscriber = $this->dataFieldCollector->collectForSubscriber(
            $contact,
            $websiteId,
            (int) $subscriberAddressBookId
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
     * @param SdkContact $response
     *
     * @return void
     * @throws AlreadyExistsException
     */
    private function markProcessedSubscriberAsImported(Contact $contact, SdkContact $response): void
    {
        if ($response) {
            $contact->setContactId($response->getContactId());
            $contact->setSubscriberImported(1);
            $this->contactResource->save($contact);
        }
    }
}
