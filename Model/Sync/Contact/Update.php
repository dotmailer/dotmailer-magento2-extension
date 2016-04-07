<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

class Update extends \Dotdigitalgroup\Email\Model\Sync\Contact\Delete
{
    public function __construct($collection)
    {
        parent::__construct($collection);
    }

    protected function _processCollection($collection)
    {
        foreach($collection as $item)
        {
            $websiteId = $item->getWebsiteId();
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
            $importData = unserialize($item->getImportData());

            if ($this->_client) {
                if ($item->getImportMode() == Dotdigitalgroup_Email_Model_Importer::MODE_CONTACT_EMAIL_UPDATE){
                    $emailBefore = $importData['emailBefore'];
                    $email = $importData['email'];
                    $isSubscribed = $importData['isSubscribed'];
                    $subscribersAddressBook = $this->_helper->getWebsiteConfig(
                        Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID, $websiteId);
                    $result = $this->_client->postContacts($emailBefore);
                    //check for matching email
                    if (isset($result->id)) {
                        if ($email != $result->email) {
                            $data = array(
                                'Email' => $email,
                                'EmailType' => 'Html'
                            );
                            //update the contact with same id - different email
                            $this->_client->updateContact($result->id, $data);
                        }
                        if (!$isSubscribed && $result->status == 'Subscribed') {
                            $this->_client->deleteAddressBookContact($subscribersAddressBook, $result->id);
                        }
                    }
                } elseif ($item->getImportMode() == Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_RESUBSCRIBED){
                    $email = $importData['email'];
                    $apiContact = $this->_client->postContacts( $email );

                    //resubscribe suppressed contacts
                    if (isset($apiContact->message) &&
                        $apiContact->message == Dotdigitalgroup_Email_Model_Apiconnector_Client::API_ERROR_CONTACT_SUPPRESSED)
                    {
                        $apiContact = $this->_client->getContactByEmail($email);
                        $result = $this->_client->postContactsResubscribe( $apiContact );
                    }
                }elseif ($item->getImportMode() == Dotdigitalgroup_Email_Model_Importer::MODE_SUBSCRIBER_UPDATE){
                    $email = $importData['email'];
                    $result = $this->_client->postContacts($email);
                    if (isset($result->id)){
                        $contactId = $result->id;
                        $this->_client->deleteAddressBookContact(
                            Mage::helper('ddg')->getSubscriberAddressBook($websiteId), $contactId
                        );
                    }
                }

                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }
}