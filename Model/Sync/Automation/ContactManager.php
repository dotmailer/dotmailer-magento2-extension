<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigitalgroup\Email\Exception\PendingOptInException;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
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
     * ContactManager constructor.
     *
     * @param Data $helper
     * @param ContactResponseHandler $contactResponseHandler
     * @param ContactResource $contactResource
     * @param DataFieldCollector $dataFieldCollector
     */
    public function __construct(
        Data $helper,
        ContactResponseHandler $contactResponseHandler,
        ContactResource $contactResource,
        DataFieldCollector $dataFieldCollector
    ) {
        $this->helper = $helper;
        $this->contactResponseHandler = $contactResponseHandler;
        $this->contactResource = $contactResource;
        $this->dataFieldCollector = $dataFieldCollector;
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
     *
     * @return int
     * @throws LocalizedException
     * @throws PendingOptInException
     */
    public function prepareDotdigitalContact(Contact $contact, Subscriber $subscriber, array $automationDataFields): int
    {
        $addressBookId = '';
        $dataFields = [];
        $email = $contact->getEmail();
        $websiteId = $contact->getWebsiteId();

        if (!$subscriber->isSubscribed() && $this->helper->isOnlySubscribersForContactSync($websiteId)) {
            throw new LocalizedException(
                __('Non-subscribed contacts cannot be enrolled.')
            );
        }

        if ($this->canPushContactToCustomerAddressBook($contact)) {
            $addressBookId = $this->helper->getCustomerAddressBook($websiteId);
            $dataFields = $this->dataFieldCollector->mergeFields(
                $automationDataFields,
                $this->dataFieldCollector->collectForCustomer(
                    $contact,
                    $websiteId
                )
            );
        } elseif ($this->canPushContactToGuestAddressBook($contact)) {
            $addressBookId = $this->helper->getGuestAddressBook($websiteId);
            $dataFields = $this->dataFieldCollector->mergeFields(
                $automationDataFields,
                $this->dataFieldCollector->collectForGuest(
                    $contact,
                    $websiteId
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
            $this->markProcessedContactAsImported($contact);
        }

        if ($subscriber->isSubscribed()) {
            $this->pushContactToSubscriberAddressBook($contact);
        }

        return $contactId;
    }

    /**
     * Check if we can push a customer to the customer address book.
     *
     * @param Contact $contact
     *
     * @return bool
     */
    private function canPushContactToCustomerAddressBook(Contact $contact): bool
    {
        if (!$contact->getCustomerId()) {
            return false;
        }

        if (!$this->helper->isCustomerSyncEnabled($contact->getWebsiteId())) {
            return false;
        }

        return true;
    }

    /**
     * Check if we can push a guest to the guest address book.
     *
     * @param Contact $contact
     *
     * @return bool
     */
    private function canPushContactToGuestAddressBook(Contact $contact): bool
    {
        if (!$contact->getIsGuest()) {
            return false;
        }

        if (!$this->helper->isGuestSyncEnabled($contact->getWebsiteId())) {
            return false;
        }

        return true;
    }

    /**
     * Push contact to Dotdigital without specifying an address book.
     *
     * @param string $email
     * @param string|int $websiteId
     * @param array $dataFields
     *
     * @return bool|\stdClass
     * @throws LocalizedException
     */
    private function pushContactToAllContacts(string $email, $websiteId, array $dataFields)
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
     * @return bool|\stdClass
     * @throws LocalizedException
     */
    private function pushContactToAddressBook(string $email, $websiteId, string $addressBookId, array $dataFields)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $response = $client->addContactToAddressBook($email, $addressBookId, null, $dataFields);

        return $this->contactResponseHandler->processContactResponse($response, $email, $websiteId);
    }

    /**
     * Add subscribers to subscriber address book
     *
     * @param Contact $contact
     *
     * @return void
     * @throws LocalizedException
     */
    private function pushContactToSubscriberAddressBook(Contact $contact): void
    {
        $websiteId = $contact->getWebsiteId();
        $subscriberSyncEnabled = $this->helper->isSubscriberSyncEnabled($websiteId);
        $subscriberAddressBookId = $this->helper->getSubscriberAddressBook($websiteId);
        if (!$subscriberSyncEnabled || !$subscriberAddressBookId) {
            return;
        }

        $client = $this->helper->getWebsiteApiClient($websiteId);
        $subscriberDataFields = $this->dataFieldCollector->mergeFields(
            [],
            $this->dataFieldCollector->collectForSubscriber(
                $contact,
                $websiteId
            )
        );
        $consentFields = $this->dataFieldCollector->extractConsentFromPreparedDataFields($subscriberDataFields);

        if (!empty($consentFields)) {
            $updateContactResponse = $client->updateContactWithConsentAndPreferences(
                $contact->getId(),
                $contact->getEmail(),
                [],
                $consentFields
            );
        }

        // optInType will be set in $subscriberDataFields if it is 'Double'
        $postAddressBookResponse = $client->addContactToAddressBook(
            $contact->getEmail(),
            $subscriberAddressBookId,
            null,
            $subscriberDataFields
        );

        if (isset($updateContactResponse->message) || isset($postAddressBookResponse->message)) {
            return;
        }

        $this->markProcessedSubscriberAsImported($contact);
    }

    /**
     * Mark contact as imported.
     *
     * @param Contact $contact
     *
     * @return void
     * @throws AlreadyExistsException
     */
    private function markProcessedContactAsImported(Contact $contact): void
    {
        $contact->setEmailImported(1);
        $this->contactResource->save($contact);
    }

    /**
     * Mark contact as imported.
     *
     * @param Contact $contact
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function markProcessedSubscriberAsImported(Contact $contact): void
    {
        $contact->setSubscriberImported(1);
        $this->contactResource->save($contact);
    }
}
