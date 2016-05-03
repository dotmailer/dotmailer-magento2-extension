<?php
namespace Dotdigitalgroup\Email\Model\Sync\Contact;

use DotMailer\Api\DataTypes\ApiContact;

class Delete extends \Dotdigitalgroup\Email\Model\Sync\Contact\Bulk
{


    protected function _processCollection($collection)
    {
        foreach($collection as $item)
        {
            $result = true;
            $websiteId = $item->getWebsiteId();
            $email = unserialize($item->getImportData());
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);

            if ($this->_client) {
                $apiContact = new ApiContact();
                $apiContact->email = $email;

                $response = $this->_client->PostContacts($apiContact);

                if (isset($apiContact->id)) {
                    $this->_client->DeleteContact($response->id);

                }elseif (! isset($response->id)) {
                    $result = $apiContact;

                }
                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }

    protected function _handleSingleItemAfterSync($item, $result)
    {
        if (! $result){

            $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::IMPORTED)
                ->setImportFinished(time())
                ->setImportStarted(time())
                ->setMessage('')
                ->save();
        }
    }
}