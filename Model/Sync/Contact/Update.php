<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

use DotMailer\Api\DataTypes\ApiContact;
use DotMailer\Api\DataTypes\ApiContactEmailTypes;

class Update extends \Dotdigitalgroup\Email\Model\Sync\Contact\Delete
{
    

    public function sync($collection)
    {
        foreach($collection as $item)
        {
            $websiteId = $item->getWebsiteId();
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
            $importData = unserialize($item->getImportData());

            if ($this->_client) {
                if ($item->getImportMode() == \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_EMAIL_UPDATE){
                    $emailBefore = $importData['emailBefore'];
                    $email = $importData['email'];
                    $isSubscribed = $importData['isSubscribed'];
                    $subscribersAddressBook = $this->_helper->getWebsiteConfig(
                        \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID, $websiteId);
                    
                    $apiContact = new ApiContact();
                    $apiContact->email = $emailBefore;
                    $result = $this->_client->PostContacts($apiContact);

                    //check for matching email
                    if (isset($result->id)) {
                        $contactId = $result->id;
                        if ($email != $result->email) {
                            //update the contact with same id - different email
                            $apiContact = new ApiContact();
                            $apiContact->id = $contactId;
                            $apiContact->email = $email;
                            $apiContact->emailType = ApiContactEmailTypes::HTML;
                            $this->_client->UpdateContact($apiContact);
                        }
                        if (! $isSubscribed && $result->status == 'Subscribed') {
                            //remove contact from addressbook
                            $this->_client->DeleteAddressBookContact($subscribersAddressBook, $contactId);
                        }
                    }
                } elseif ($item->getImportMode() == \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED){
                    $email = $importData['email'];
                    $apiContact = new ApiContact();
                    $apiContact->email = $email;
                    $apiContact = $this->_client->PostContacts( $apiContact );

                    //resubscribe suppressed contacts
                    if (isset($apiContact->message) &&
                        $apiContact->message == \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_CONTACT_SUPPRESSED)
                    {
                        //$apiContact = $this->_client->getContactByEmail($email);
                        $apiContact = new ApiContact();
                        $apiContact->email = $email;
                        $response = $this->_client->PostContactsResubscribe( $apiContact );
                    }
                }elseif ($item->getImportMode() == \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_UPDATE){
                    $email = $importData['email'];
                    $result = $this->_client->postContacts($email);
                    if (isset($result->id)){
                        $contactId = $result->id;
                        $this->_client->deleteAddressBookContact(
                            $this->_helper->getSubscriberAddressBook($websiteId), $contactId
                        );
                    }
                }

                $this->_handleSingleItemAfterSync($item, $response);
            }
        }
    }
}