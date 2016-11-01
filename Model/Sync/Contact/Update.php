<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact;

class Update extends Delete
{

    /**
     * @var Contact
     */
    protected $contactResource;

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
    protected $suppressedContactIds;

    /**
     * Sync.
     *
     * @param $collection
     */
    public function sync($collection)
    {
        foreach ($collection as $item) {
            $websiteId = $item->getWebsiteId();
            if ($this->_helper->isEnabled($websiteId)) {
                $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
                $importData = unserialize($item->getImportData());
                $result = true;

                if ($this->_client) {
                    if ($item->getImportMode() == \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_EMAIL_UPDATE) {
                        $emailBefore = $importData['emailBefore'];
                        $email = $importData['email'];
                        $isSubscribed = $importData['isSubscribed'];
                        $subscribersAddressBook = $this->_helper->getWebsiteConfig(
                            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
                            $websiteId
                        );

                        $result = $this->_client->postContacts($emailBefore);

                        //check for matching email
                        if (isset($result->id)) {
                            if ($email != $result->email) {
                                $data = [
                                    'Email' => $email,
                                    'EmailType' => 'Html',
                                ];
                                //update the contact with same id - different email
                                $this->_client->updateContact($result->id, $data);
                            }
                            if (!$isSubscribed && $result->status == 'Subscribed') {
                                $this->_client->deleteAddressBookContact($subscribersAddressBook, $result->id);
                            }
                        }
                    } elseif ($item->getImportMode()
                        == \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED
                    ) {
                        $email = $importData['email'];
                        $apiContact = $this->_client->postContacts($email);

                        //resubscribe suppressed contacts
                        if (isset($apiContact->message) &&
                            $apiContact->message
                            == \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_CONTACT_SUPPRESSED
                        ) {
                            $apiContact = $this->_client->getContactByEmail($email);
                            $result = $this->_client->postContactsResubscribe($apiContact);
                        }
                    } elseif ($item->getImportMode() == \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_UPDATE) {
                        $email = $importData['email'];
                        $id = $importData['id'];
                        $result = $this->_client->postContacts($email);
                        if (isset($result->id)) {
                            $contactId = $result->id;
                            $this->_client->deleteAddressBookContact(
                                $this->_helper->getSubscriberAddressBook($websiteId), $contactId
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
        $this->contactResource->setContactSuppressedForContactIds($this->suppressedContactIds);
    }
}
