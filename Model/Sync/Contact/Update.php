<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

class Update extends \Dotdigitalgroup\Email\Model\Sync\Contact\Delete
{
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
                            //@codingStandardsIgnoreStart
                            $contactEmail = $this->_contactFactory->create()
                                ->load($id);
                            if ($contactEmail->getId()) {
                                $contactEmail->setSuppressed('1')
                                    ->save();
                            }
                            //@codingStandardsIgnoreEnd
                        }
                    }

                    $this->_handleSingleItemAfterSync($item, $result);
                }
            }
        }
    }
}
