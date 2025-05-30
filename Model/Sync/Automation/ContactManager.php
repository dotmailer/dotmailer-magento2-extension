<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigitalgroup\Email\Exception\PendingOptInException;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SingleSubscriberSyncer;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;

class ContactManager
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ContactResponseHandler
     */
    private $contactResponseHandler;

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
     * @param Data $helper
     * @param ContactResponseHandler $contactResponseHandler
     * @param ContactResource $contactResource
     * @param DataFieldCollector $dataFieldCollector
     * @param SingleSubscriberSyncer $singleSubscriberSyncer
     */
    public function __construct(
        Data $helper,
        ContactResponseHandler $contactResponseHandler,
        ContactResource $contactResource,
        DataFieldCollector $dataFieldCollector,
        SingleSubscriberSyncer $singleSubscriberSyncer
    ) {
        $this->helper = $helper;
        $this->contactResponseHandler = $contactResponseHandler;
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
     *
     * @return int
     * @throws LocalizedException
     * @throws PendingOptInException
     */
    public function prepareDotdigitalContact(
        Contact $contact,
        Subscriber $subscriber,
        array $automationDataFields,
        string $automationType
    ): int {
        $addressBookId = '';
        $dataFields = [];
        $email = $contact->getEmail();
        $websiteId = (int) $contact->getWebsiteId();

        if ($this->canPushContactToCustomerAddressBook($contact, $subscriber, $automationType)) {
            $addressBookId = $this->helper->getCustomerAddressBook($websiteId);
            $dataFields = $this->dataFieldCollector->mergeFields(
                $automationDataFields,
                $this->dataFieldCollector->collectForCustomer(
                    $contact,
                    $websiteId,
                    (int) $addressBookId
                )
            );
        } elseif ($this->canPushContactToGuestAddressBook($contact, $subscriber, $automationType)) {
            $addressBookId = $this->helper->getGuestAddressBook($websiteId);
            $dataFields = $this->dataFieldCollector->mergeFields(
                $automationDataFields,
                $this->dataFieldCollector->collectForGuest(
                    $contact,
                    $websiteId,
                    (int) $addressBookId
                )
            );
        }

        $response = $addressBookId ?
            $this->pushContactToAddressBook($email, $websiteId, $addressBookId, $dataFields) :
            $this->pushContactToAllContacts($email, $websiteId, $automationDataFields);

        $status = $this->contactResponseHandler->getStatusFromResponse($response);
        $contactId = $this->contactResponseHandler->getContactIdFromResponse($response);

        if ($status === StatusInterface::PENDING_OPT_IN) {
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
     * Push contact to Dotdigital without specifying an address book.
     *
     * @param string $email
     * @param int $websiteId
     * @param array $dataFields
     *
     * @return bool|\stdClass
     * @throws LocalizedException
     */
    private function pushContactToAllContacts(string $email, int $websiteId, array $dataFields)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $response = $client->postContactWithConsentAndPreferences($email, $dataFields);

        return $this->contactResponseHandler->processContactResponse($response, $email, $websiteId);
    }

    /**
     * Add contact to an address book.
     *
     * @param string $email
     * @param int $websiteId
     * @param string $addressBookId
     * @param array $dataFields
     *
     * @return \stdClass
     * @throws LocalizedException
     */
    private function pushContactToAddressBook(string $email, int $websiteId, string $addressBookId, array $dataFields)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $response = $client->addContactToAddressBook($email, $addressBookId, null, $dataFields);

        return $this->contactResponseHandler->processContactResponse($response, $email, $websiteId);
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
