<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;

/**
 * Handle update data for importer.
 */
class Update extends Delete
{

    /**
     * @var Contact
     */
    public $contactResource;

    /**
     * Update constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
     * @param \Dotdigitalgroup\Email\Model\Config\Json $serializer
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param Contact $contactResource
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        \Dotdigitalgroup\Email\Model\Config\Json $serializer,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        Contact $contactResource
    ) {
        $this->contactResource = $contactResource;

        parent::__construct($helper, $importerResource, $serializer, $contactFactory);
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
                $this->client = $this->helper->getWebsiteApiClient($websiteId);
                $importData = $this->serializer->unserialize($item->getImportData());

                $this->syncItem($item, $importData, $websiteId);
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
        if ($this->client) {
            if ($item->getImportMode() == Importer::MODE_CONTACT_EMAIL_UPDATE) {
                $result = $this->syncItemContactEmailUpdateMode($importData, $websiteId);
            } elseif ($item->getImportMode() == Importer::MODE_SUBSCRIBER_RESUBSCRIBED) {
                $result = $this->syncItemSubscriberResubscribedMode($importData);
            } elseif ($item->getImportMode() == Importer::MODE_SUBSCRIBER_UPDATE) {
                $result = $this->syncItemSubscriberUpdateMode($importData, $websiteId);
            }

            if (isset($result)) {
                $this->_handleSingleItemAfterSync($item, $result);
            }
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
     *
     * @return mixed
     */
    private function syncItemSubscriberResubscribedMode($importData)
    {
        $email = $importData['email'];
        $apiContact = $this->client->postContacts($email);

        //resubscribe suppressed contacts
        if (isset($apiContact->message) &&
            $apiContact->message
            == \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_CONTACT_SUPPRESSED
        ) {
            $apiContact = $this->client->getContactByEmail($email);
            return $this->client->postContactsResubscribe($apiContact);
        }

        return false;
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
