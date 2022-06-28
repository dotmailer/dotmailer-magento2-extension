<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Importer as ModelImporter;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\SingleItemPostProcessorFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Newsletter\Model\Subscriber;

/**
 * Handle update data for importer.
 */
class Update extends AbstractItemSyncer
{
    public const ERROR_CONTACT_ALREADY_SUBSCRIBED = 'Contact is already subscribed';
    public const ERROR_CONTACT_CHALLENGED = 'Contact must confirm resubscription via automated resubscribe email';
    public const ERROR_CONTACT_CANNOT_BE_UNSUPPRESSED = 'Contact cannot be unsuppressed';
    public const ERROR_FEATURE_NOT_AVAILABLE = 'This feature is not available in the version of the API you are using';

    public const IGNORED_ERRORS = [
        'Contact is suppressed. ERROR_CONTACT_SUPPRESSED'
    ];

    /**
     * @var Contact
     */
    public $contactResource;

    /**
     * @var ContactData
     */
    private $contactData;

    /**
     * @var SingleItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * Keep the suppressed contact ids that needs to update.
     *
     * @var array
     */
    private $suppressedContactIds;

    /**
     * Update constructor.
     *
     * @param Data $helper
     * @param File $fileHelper
     * @param SerializerInterface $serializer
     * @param Importer $importerResource
     * @param Contact $contactResource
     * @param ContactData $contactData
     * @param SingleItemPostProcessorFactory $postProcessor
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Data $helper,
        File $fileHelper,
        SerializerInterface $serializer,
        Importer $importerResource,
        Contact $contactResource,
        ContactData $contactData,
        SingleItemPostProcessorFactory $postProcessor,
        Logger $logger,
        array $data = []
    ) {
        $this->contactResource = $contactResource;
        $this->contactData = $contactData;
        $this->postProcessor = $postProcessor;
        parent::__construct($helper, $fileHelper, $serializer, $importerResource, $logger, $data);
    }

    /**
     * Sync.
     *
     * @param mixed $collection
     *
     * @return void
     */
    public function sync($collection)
    {
        $this->client = $this->getClient();
        foreach ($collection as $item) {
            try {
                $this->process($item);
            } catch (\InvalidArgumentException $e) {
                $this->logger->debug(
                    sprintf(
                        'Error processing %s import data for ID: %d',
                        $item->getImportType(),
                        $item->getImportId()
                    ),
                    [(string)$e]
                );
            }
        }
        //update suppress status for contact ids
        if (!empty($this->suppressedContactIds)) {
            $this->contactResource->setContactSuppressedForContactIds($this->suppressedContactIds);
        }
    }

    /**
     * Process each Importer item.
     *
     * @param mixed $item
     * @return void
     */
    protected function process($item)
    {
        $websiteId = $item->getWebsiteId();
        $importData = $this->serializer->unserialize($item->getImportData());

        $this->syncItem($item, $importData, $websiteId);
    }

    /**
     * Sync the data depending on the import type.
     *
     * @param mixed $item
     * @param array $importData
     * @param string|int $websiteId
     *
     * @return void
     */
    private function syncItem($item, $importData, $websiteId)
    {
        $apiMessage = $result = null;

        switch ($item->getImportMode()) {
            case ModelImporter::MODE_CONTACT_EMAIL_UPDATE:
                $result = $this->syncItemContactEmailUpdateMode($importData);
                break;

            case ModelImporter::MODE_SUBSCRIBER_RESUBSCRIBED:
                $result = $this->syncItemSubscriberResubscribedMode($importData, $websiteId);
                $apiMessage = $this->handleResubscribeResponseStatus($result);
                break;

            case ModelImporter::MODE_SUBSCRIBER_UPDATE:
                $result = $this->syncItemSubscriberUpdateMode($importData, $websiteId);
                break;
        }

        if ($result) {
            $this->postProcessor->create(['data' => ['client' => $this->client]])
                ->handleItemAfterSync($item, $result, $apiMessage);
        }
    }

    /**
     * Sync Contact_Email_Update.
     *
     * @param array $importData
     * @return \StdClass
     */
    private function syncItemContactEmailUpdateMode($importData)
    {
        $emailBefore = $importData['emailBefore'];
        $email = $importData['email'];
        $result = $this->client->postContacts($emailBefore);

        //check for matching email
        if (isset($result->id)) {
            if ($email != $result->email) {
                $data = [
                    'Email' => $email,
                    'EmailType' => 'Html',
                ];
                //update the contact with same id - different email
                $this->client->updateContact($result->id, $data);
            }
        }
        return $result;
    }

    /**
     * Sync Subscriber_Resubscribed.
     *
     * @param array $importData
     * @param int $websiteId
     *
     * @return \StdClass
     */
    private function syncItemSubscriberResubscribedMode($importData, $websiteId)
    {
        $email = $importData['email'];
        $apiContact = $this->client->postContacts($email);

        //resubscribe suppressed contacts
        if (isset($apiContact->message) &&
            $apiContact->message
            == \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_CONTACT_SUPPRESSED
        ) {
            $subscribersAddressBook = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
                $websiteId
            );
            return ($subscribersAddressBook) ?
                $this->client->postAddressBookContactResubscribe($subscribersAddressBook, $email) :
                $this->client->postContactsResubscribe($this->client->getContactByEmail($email));
        }

        return $apiContact;
    }

    /**
     * Sync Subscriber_Update.
     *
     * @param array $importData
     * @param string|int $websiteId
     *
     * @return \StdClass
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function syncItemSubscriberUpdateMode($importData, $websiteId)
    {
        $email = $importData['email'];
        $id = $importData['id'];

        $data[] = [
            'Key' => 'SUBSCRIBER_STATUS',
            'Value' => $this->contactData->getSubscriberStatusString(
                Subscriber::STATUS_UNSUBSCRIBED
            )
        ];

        /** @var \StdClass $result */
        $result = $this->client->updateContactDatafieldsByEmail($email, $data);

        if (isset($result->id)) {
            $contactId = $result->id;
            $this->client->deleteAddressBookContact(
                $this->helper->getSubscriberAddressBook($websiteId),
                $contactId
            );
        } else {
            $this->suppressedContactIds[] = $id;
            // Data field updates for suppressed contacts will not be marked as failed imports
            if (isset($result->message) && in_array($result->message, self::IGNORED_ERRORS)) {
                $result->ignoredMessage = $result->message;
                unset($result->message);
            }
        }
        return $result;
    }

    /**
     * Map response status text to one of our class constants.
     *
     * @param \StdClass $response
     * @return string|null
     */
    private function handleResubscribeResponseStatus($response)
    {
        if (!isset($response->status)) {
            return null;
        }

        switch ($response->status) {
            case 'Subscribed':
                return self::ERROR_CONTACT_ALREADY_SUBSCRIBED;
            case 'ContactChallenged':
                return self::ERROR_CONTACT_CHALLENGED;
            case 'ContactCannotBeUnsuppressed':
                return self::ERROR_CONTACT_CANNOT_BE_UNSUPPRESSED;
            case 'NotAvailableInThisVersion':
                return self::ERROR_FEATURE_NOT_AVAILABLE;
            default:
                return null;
        }
    }
}
