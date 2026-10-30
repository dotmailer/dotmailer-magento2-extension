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
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
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
     * @var RegistrationStoreResolver
     */
    private $registrationStoreResolver;

    /**
     * ContactManager constructor.
     *
     * @param DotdigitalContactFactory $sdkContactFactory
     * @param Data $helper
     * @param ClientFactory $clientFactory
     * @param ContactResource $contactResource
     * @param DataFieldCollector $dataFieldCollector
     * @param SingleSubscriberSyncer $singleSubscriberSyncer
     * @param RegistrationStoreResolver $registrationStoreResolver
     */
    public function __construct(
        DotdigitalContactFactory $sdkContactFactory,
        Data $helper,
        ClientFactory $clientFactory,
        ContactResource $contactResource,
        DataFieldCollector $dataFieldCollector,
        SingleSubscriberSyncer $singleSubscriberSyncer,
        RegistrationStoreResolver $registrationStoreResolver
    ) {
        $this->sdkContactFactory = $sdkContactFactory;
        $this->helper = $helper;
        $this->clientFactory = $clientFactory;
        $this->contactResource = $contactResource;
        $this->dataFieldCollector = $dataFieldCollector;
        $this->singleSubscriberSyncer = $singleSubscriberSyncer;
        $this->registrationStoreResolver = $registrationStoreResolver;
    }

    /**
     * Prepare a contact in Dotdigital.
     *
     * Ensure that, prior to automation enrolment, we have a contact in Dotdigital,
     * in the expected address books, and with the expected data fields.
     *
     * Customers go to the customer list of their registration store, not the store they are shopping in.
     * Guests go to the guest list of the store the automation was triggered in when it belongs to the website,
     * otherwise of their contact store (email_contact.store_id), as in the guest cron sync,
     * or the website's guest list when the contact has no store.
     *
     * @param Contact $contact
     * @param Subscriber $subscriber
     * @param array $automationDataFields
     * @param string $automationType
     * @param string|null $optInType
     * @param int|null $triggerStoreId
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
        ?string $optInType = null,
        ?int $triggerStoreId = null
    ): int {
        $addressBookId = 0;
        $email = $contact->getEmail();
        $websiteId = (int) $contact->getWebsiteId();
        $dataFields = $automationDataFields;

        if ($this->canPushContactToCustomerAddressBook($contact, $subscriber, $automationType)) {
            $registrationStoreId = $this->registrationStoreResolver->resolve(
                (int) $contact->getCustomerId(),
                $websiteId
            );
            $addressBookId = (int) $this->helper->getCustomerListByStoreId($registrationStoreId);
            $dataFields = $this->dataFieldCollector->mergeFields(
                $automationDataFields,
                $this->dataFieldCollector->collectForCustomer(
                    $contact,
                    $websiteId,
                    $addressBookId
                )
            );
        } elseif ($this->canPushContactToGuestAddressBook($contact, $subscriber, $automationType)) {
            $addressBookId = (int) $this->helper->getGuestListByStoreId(
                $this->resolveGuestStoreId($contact, $websiteId, $triggerStoreId)
            );
            $dataFields = $this->dataFieldCollector->mergeFields(
                $automationDataFields,
                $this->dataFieldCollector->collectForGuest(
                    $contact,
                    $websiteId,
                    $addressBookId
                )
            );
        }

        $sdkContact = $this->buildSdkContact(
            $email,
            $this->dataFieldCollector->flatten($dataFields),
            $optInType,
            $addressBookId
        );

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
            $this->singleSubscriberSyncer->execute(
                $contact,
                (int) $subscriber->getStoreId() ?: null
            );
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
     * Resolve the store whose guest list a contact belongs to.
     *
     * Mirrors the guest sync: a contact whose store is missing or outside the website goes to the
     * website's default store.
     *
     * @param Contact $contact
     * @param int $websiteId
     * @param int|null $triggerStoreId
     *
     * @return int
     * @throws LocalizedException
     */
    private function resolveGuestStoreId(Contact $contact, int $websiteId, ?int $triggerStoreId = null): int
    {
        $storeId = $triggerStoreId ?: (int) $contact->getStoreId();

        return in_array($storeId, $this->registrationStoreResolver->getWebsiteStoreIds($websiteId), true)
            ? $storeId
            : $this->registrationStoreResolver->getFallbackStoreId($websiteId);
    }

    /**
     * Check whether the contact can be pushed to the guest address book.
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
