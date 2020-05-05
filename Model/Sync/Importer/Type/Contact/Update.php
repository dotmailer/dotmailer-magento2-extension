<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact;

use Dotdigitalgroup\Email\Model\Apiconnector\Customer;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\SingleItemPostProcessorFactory;

/**
 * Handle update data for importer.
 */
class Update extends AbstractItemSyncer
{
    const ERROR_CONTACT_ALREADY_SUBSCRIBED = 'Contact is already subscribed';
    const ERROR_CONTACT_CHALLENGED = 'Contact must confirm resubscription via automated resubscribe email';
    const ERROR_CONTACT_CANNOT_BE_UNSUPPRESSED = 'Contact cannot be unsuppressed';
    const ERROR_FEATURE_NOT_AVAILABLE = 'This feature is not available in the version of the API you are using';

    /**
     * @var Contact
     */
    public $contactResource;

    /**
     * @var Customer
     */
    private $apiConnectorCustomer;

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
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
     * @param Contact $contactResource
     * @param Customer $apiConnectorCustomer
     * @param SingleItemPostProcessorFactory $postProcessor
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        Contact $contactResource,
        Customer $apiConnectorCustomer,
        SingleItemPostProcessorFactory $postProcessor,
        array $data = []
    ) {
        $this->contactResource = $contactResource;
        $this->apiConnectorCustomer = $apiConnectorCustomer;
        $this->postProcessor = $postProcessor;

        parent::__construct($helper, $fileHelper, $serializer, $importerResource, $data);
    }

    /**
     * Sync.
     *
     * @param mixed $collection
     *
     * @return null
     */
    public function sync($collection)
    {
        $this->client = $this->getClient();

        foreach ($collection as $item) {
            $this->process($item);
        }
        //update suppress status for contact ids
        if (!empty($this->suppressedContactIds)) {
            $this->contactResource->setContactSuppressedForContactIds($this->suppressedContactIds);
        }
    }

    protected function process($item)
    {
        $websiteId = $item->getWebsiteId();
        $importData = $this->serializer->unserialize($item->getImportData());

        $this->syncItem($item, $importData, $websiteId);
    }

    /**
     * @param mixed $item
     * @param mixed $importData
     * @param mixed $websiteId
     *
     * @return null
     */
    private function syncItem($item, $importData, $websiteId)
    {
        $apiMessage = $result = null;

        switch ($item->getImportMode()) {
            case Importer::MODE_CONTACT_EMAIL_UPDATE:
                $result = $this->syncItemContactEmailUpdateMode($importData, $websiteId);
                break;

            case Importer::MODE_SUBSCRIBER_RESUBSCRIBED:
                $result = $this->syncItemSubscriberResubscribedMode($importData, $websiteId);
                $apiMessage = $this->handleResubscribeResponseStatus($result);
                break;

            case Importer::MODE_SUBSCRIBER_UPDATE:
                $result = $this->syncItemSubscriberUpdateMode($importData, $websiteId);
                break;
        }

        if ($result) {
            $this->postProcessor->create(['data' => ['client' => $this->client]])
                ->handleItemAfterSync($item, $result, $apiMessage);
        }
    }

    /**
     * @param mixed $importData
     * @param mixed $websiteId
     *
     * @return mixed
     */
    private function syncItemContactEmailUpdateMode($importData, $websiteId)
    {
        $emailBefore = $importData['emailBefore'];
        $email = $importData['email'];
        $isSubscribed = $importData['isSubscribed'];
        $subscribersAddressBook = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            $websiteId
        );

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
            if (!$isSubscribed && $result->status == 'Subscribed') {
                $this->client->deleteAddressBookContact($subscribersAddressBook, $result->id);
            }
        }
        return $result;
    }

    /**
     * @param mixed $importData
     * @param int $websiteId
     *
     * @return mixed
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
     * @param mixed $importData
     * @param mixed $websiteId
     *
     * @return mixed
     */
    private function syncItemSubscriberUpdateMode($importData, $websiteId)
    {
        $email = $importData['email'];
        $id = $importData['id'];

        $subscriberStatuses = $this->apiConnectorCustomer->subscriberStatus;
        $unsubscribedValue = $subscriberStatuses[\Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED];
        $data[] = [
            'Key' => 'SUBSCRIBER_STATUS',
            'Value' => $unsubscribedValue
        ];

        $result = $this->client->updateContactDatafieldsByEmail($email, $data);

        if (isset($result->id)) {
            $contactId = $result->id;
            $this->client->deleteAddressBookContact(
                $this->helper->getSubscriberAddressBook($websiteId),
                $contactId
            );
        } else {
            //suppress contacts
            $this->suppressedContactIds[] = $id;
        }
        return $result;
    }

    /**
     * @param $response
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
