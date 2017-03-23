<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;

class Update extends Delete
{

    /**
     * @var Contact
     */
    public $contactResource;

    /**
     * Update constructor.
     *
     * @param Contact                                     $contactResource
     * @param \Dotdigitalgroup\Email\Helper\Data          $helper
     * @param \Dotdigitalgroup\Email\Helper\File          $fileHelper
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     */
    public function __construct(
        Contact $contactResource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
    ) {
        $this->contactResource = $contactResource;

        parent::__construct($helper, $fileHelper, $contactFactory);
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
     * @param $collection
     */
    public function sync($collection)
    {
        foreach ($collection as $item) {
            $websiteId = $item->getWebsiteId();
            if ($this->helper->isEnabled($websiteId)) {
                $this->client = $this->helper->getWebsiteApiClient($websiteId);
                //@codingStandardsIgnoreStart
                $importData = unserialize($item->getImportData());
                //@codingStandardsIgnoreEnd
                $result = true;

                if ($this->client) {
                    if ($item->getImportMode() == Importer::MODE_CONTACT_EMAIL_UPDATE) {
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
                    } elseif ($item->getImportMode()
                        == Importer::MODE_SUBSCRIBER_RESUBSCRIBED
                    ) {
                        $email = $importData['email'];
                        $apiContact = $this->client->postContacts($email);

                        //resubscribe suppressed contacts
                        if (isset($apiContact->message) &&
                            $apiContact->message
                            == \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_CONTACT_SUPPRESSED
                        ) {
                            $apiContact = $this->client->getContactByEmail($email);
                            $result = $this->client->postContactsResubscribe($apiContact);
                        }
                    } elseif ($item->getImportMode() == Importer::MODE_SUBSCRIBER_UPDATE) {
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
                    }

                    $this->_handleSingleItemAfterSync($item, $result);
                }
            }
        }
        //update suppress status for contact ids
        if (!empty($this->suppressedContactIds)) {
            $this->contactResource->setContactSuppressedForContactIds($this->suppressedContactIds);
        }
    }
}
