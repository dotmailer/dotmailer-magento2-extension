<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigital\V3\Models\Contact as ContactModel;
use Dotdigital\V3\Models\ContactFactory as DotdigitalContactFactory;
use Dotdigitalgroup\Email\Exception\PendingOptInException;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\StatusInterface as V3StatusInterface;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SingleSubscriberSyncer;
use Http\Client\Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;

class ContactManager
{
    /**
     * @var DotdigitalContactFactory
     */
    private $sdkContactFactory;

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
     * @var SingleSubscriberSyncer
     */
    private $singleSubscriberSyncer;

    /**
     * ContactManager constructor.
     *
     * @param DotdigitalContactFactory $sdkContactFactory
     * @param Data $helper
     * @param ClientFactory $clientFactory
     * @param ContactResource $contactResource
     * @param DataFieldCollector $dataFieldCollector
     * @param SingleSubscriberSyncer $singleSubscriberSyncer
     */
    public function __construct(
        DotdigitalContactFactory $sdkContactFactory,
        Data $helper,
        ClientFactory $clientFactory,
        ContactResource $contactResource,
        DataFieldCollector $dataFieldCollector,
        SingleSubscriberSyncer $singleSubscriberSyncer
    ) {
        $this->sdkContactFactory = $sdkContactFactory;
        $this->helper = $helper;
        $this->clientFactory = $clientFactory;
        $this->contactResource = $contactResource;
        $this->dataFieldCollector = $dataFieldCollector;
        $this->singleSubscriberSyncer = $singleSubscriberSyncer;
    }

    /**
     * Prepare a contact in Dotdigital.
     *
     * Ensure that, prior to automation enrolment, we have a contact in Dotdigital,
     * in the expected address books, and with the expected data fields.
     *
     * @param Contact $contact
     * @param Subscriber $subscriber
     * @param array $automationDataFields
     * @param string $automationType
     * @param string|null $optInType
     *
     * @return int
     * @throws AlreadyExistsException
     * @throws Exception
     * @throws LocalizedException
     * @throws PendingOptInException
     */
    public function prepareDotdigitalContact(
        Contact $contact,
        Subscriber $subscriber,
        array $automationDataFields,
        string $automationType,
        ?string $optInType = null
    ): int {
        $addressBookId = 0;
        $dataFields = [];
        $email = $contact->getEmail();
        $websiteId = (int) $contact->getWebsiteId();

        if ($this->canPushContactToCustomerAddressBook($contact, $subscriber, $automationType)) {
            $addressBookId = (int) $this->helper->getCustomerAddressBook($websiteId);
            $dataFields = $this->dataFieldCollector->mergeFields(
                $automationDataFields,
                $this->dataFieldCollector->collectForCustomer(
                    $contact,
                    $websiteId,
                    $addressBookId
                )
            );
        } elseif ($this->canPushContactToGuestAddressBook($contact, $subscriber, $automationType)) {
            $addressBookId = (int) $this->helper->getGuestAddressBook($websiteId);
            $dataFields = $this->dataFieldCollector->mergeFields(
                $automationDataFields,
                $this->dataFieldCollector->collectForGuest(
                    $contact,
                    $websiteId,
                    $addressBookId
                )
            );
        }

        $sdkContact = $this->buildSdkContact($email, $dataFields, $optInType, $addressBookId);

        $response = $this->pushContactToDotdigital(
            $email,
            $sdkContact,
            $websiteId
        );

        $contactId = $response->getContactId();

        if ($response->getChannelProperties()->getEmail()->getStatus() === V3StatusInterface::PENDING_OPT_IN) {
            throw new PendingOptInException(__('Contact status is PendingOptIn, cannot be enrolled.'));
        }

        if ($addressBookId) {
            $this->markProcessedContactAsImported($contact, $contactId);
        }

        if ($subscriber->isSubscribed()) {
            $this->singleSubscriberSyncer->execute($contact);
        }

        return $contactId;
    }

    /**
     * Check if we can push a customer to the customer address book.
     *
     * @param Contact $contact
     * @param Subscriber $subscriber
     * @param string $automationType
     *
     * @return bool
     */
    private function canPushContactToCustomerAddressBook(
        Contact $contact,
        Subscriber $subscriber,
        string $automationType
    ): bool {
        if (!$contact->getCustomerId()) {
            return false;
        }

        if (!$this->helper->isCustomerSyncEnabled($contact->getWebsiteId())) {
            return false;
        }

        if ($this->weAreEnrollingViaAbandonedCartNonSubscriberLoophole(
            $contact->getWebsiteId(),
            $contact->getStoreId(),
            $subscriber->isSubscribed(),
            $automationType
        )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Check if we can push a guest to the guest address book.
     *
     * @param Contact $contact
     * @param Subscriber $subscriber
     * @param string $automationType
     *
     * @return bool
     */
    private function canPushContactToGuestAddressBook(
        Contact $contact,
        Subscriber $subscriber,
        string $automationType
    ): bool {
        if (!$contact->getIsGuest()) {
            return false;
        }

        if (!$this->helper->isGuestSyncEnabled($contact->getWebsiteId())) {
            return false;
        }

        if ($this->weAreEnrollingViaAbandonedCartNonSubscriberLoophole(
            $contact->getWebsiteId(),
            $contact->getStoreId(),
            $subscriber->isSubscribed(),
            $automationType
        )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Build SDK contact.
     *
     * If an address book ID is provided, the contact will be added to that address book.
     *
     * @param string $email
     * @param array $dataFields
     * @param string|null $optInType
     * @param int $addressBookId
     *
     * @return ContactModel
     * @throws \Exception
     */
    private function buildSdkContact(
        string $email,
        array $dataFields,
        ?string $optInType,
        int $addressBookId
    ): ContactModel {
        $contact = $this->sdkContactFactory->create();
        $contact->setMatchIdentifier('email');
        $contact->setIdentifiers(['email' => $email]);

        if ($optInType) {
            $contact->setChannelProperties([
                'email' => [
                    'optInType' => $optInType
                ]
            ]);
        }

        $contact->setDataFields($dataFields);

        if ($addressBookId) {
            $contact->setLists([(int) $addressBookId]);
        }

        return $contact;
    }

    /**
     * Push contact to Dotdigital.
     *
     * This method does not return a 'processed' v3 contact response via the ContactResponseHandler,
     * because that ends up doing the same things multiple times.
     *
     * @param string $email
     * @param ContactModel $contact
     * @param int $websiteId
     *
     * @return ContactModel
     * @throws Exception
     */
    private function pushContactToDotdigital(
        string $email,
        ContactModel $contact,
        int $websiteId
    ): ContactModel {
        $client = $this->clientFactory
            ->create(['data' => ['websiteId' => $websiteId]]);

        return $client->contacts->patchByIdentifier(
            $email,
            $contact
        );
    }

    /**
     * Mark contact as imported.
     *
     * @param Contact $contact
     * @param int $contactId
     *
     * @return void
     * @throws AlreadyExistsException
     */
    private function markProcessedContactAsImported(Contact $contact, int $contactId): void
    {
        $contact->setContactId($contactId);
        $contact->setEmailImported(1);
        $this->contactResource->save($contact);
    }

    /**
     * Determine if we are enrolling via the 'allow subscribers for AC' loophole.
     *
     * If the enrolment is 'abandoned_cart_automation'
     * and we're not allowed to enrol non-subscribers as a general rule
     * but we're allowing AC non-subscribers as a special case
     * then OK
     * but
     * don't put them into any lists in Dotdigital.
     *
     * @param string|int $websiteId
     * @param string|int $storeId
     * @param bool $isSubscribed
     * @param string $automationType
     *
     * @return bool
     */
    private function weAreEnrollingViaAbandonedCartNonSubscriberLoophole(
        $websiteId,
        $storeId,
        bool $isSubscribed,
        string $automationType
    ): bool {
        return $automationType === AutomationTypeHandler::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT &&
            !$isSubscribed &&
            $this->helper->isOnlySubscribersForContactSync($websiteId) &&
            !$this->helper->isOnlySubscribersForAC($storeId);
    }
}
