<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Dotdigitalgroup\Email\Model\Apiconnector\EngagementCloudAddressBookApiFactory;

/**
 * Handle update data for importer.
 */
class Update extends Bulk
{
    const ERROR_CONTACT_ALREADY_SUBSCRIBED = 'Contact is already subscribed';

    /**
     * @var Contact
     */
    public $contactResource;

    /**
     * @var EngagementCloudAddressBookApiFactory
     */
    private $engagementCloudAddressBookApiFactory;

    /**
     * Update constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
     * @param \Dotdigitalgroup\Email\Model\Config\Json $serializer
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param Contact $contactResource
     * @param EngagementCloudAddressBookApiFactory $engagementCloudAddressBookApiFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        \Dotdigitalgroup\Email\Model\Config\Json $serializer,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        Contact $contactResource,
        EngagementCloudAddressBookApiFactory $engagementCloudAddressBookApiFactory
    ) {
        $this->contactResource = $contactResource;
        $this->engagementCloudAddressBookApiFactory = $engagementCloudAddressBookApiFactory;

        parent::__construct($helper, $fileHelper, $importerResource, $serializer, $contactFactory, $dateTime);
    }

    /**
     * Keep the suppressed contact ids that needs to update.
     *
     * @var array
     */
    public $suppressedContactIds;

    /**
     * Sync.
     *
     * @param mixed $collection
     *
     * @return null
     */
    public function sync($collection)
    {
        foreach ($collection as $item) {
            $websiteId = $item->getWebsiteId();
            if ($this->helper->isEnabled($websiteId)) {
                $this->client = $this->engagementCloudAddressBookApiFactory->create()
                    ->setRequiredDataForClient($websiteId);
                $importData = $this->serializer->unserialize($item->getImportData());

                if ($this->client) {
                    $this->syncItem($item, $importData, $websiteId);
                }
            }
        }
        //update suppress status for contact ids
        if (!empty($this->suppressedContactIds)) {
            $this->contactResource->setContactSuppressedForContactIds($this->suppressedContactIds);
        }
    }

    /**
     * @param mixed $item
     * @param mixed $importData
     * @param mixed $websiteId
     *
     * @return null
     */
    public function syncItem($item, $importData, $websiteId)
    {
        $apiMessage = $result = null;

        switch ($item->getImportMode()) {
            case Importer::MODE_CONTACT_EMAIL_UPDATE:
                $result = $this->syncItemContactEmailUpdateMode($importData, $websiteId);
                break;

            case Importer::MODE_SUBSCRIBER_RESUBSCRIBED:
                $result = $this->syncItemSubscriberResubscribedMode($importData, $websiteId);
                if (($result->status ?? null) == 'Subscribed') {
                    // the contact is already subscribed in EC and cannot be resubscribed
                    $apiMessage = self::ERROR_CONTACT_ALREADY_SUBSCRIBED;
                }
                break;

            case Importer::MODE_SUBSCRIBER_UPDATE:
                $result = $this->syncItemSubscriberUpdateMode($importData, $websiteId);
                break;
        }

        if ($result) {
            $this->handleSingleItemAfterSync($item, $result, $apiMessage);
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
        $result = $this->client->postContacts($email);
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
}
