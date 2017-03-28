<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

class Delete extends \Dotdigitalgroup\Email\Model\Sync\Contact\Bulk
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
            //@codingStandardsIgnoreStart
            $email = unserialize($item->getImportData());
            //@codingStandardsIgnoreEnd
            if ($this->helper->isEnabled($websiteId)) {
                $this->client = $this->helper->getWebsiteApiClient($websiteId);

                if ($this->client) {
                    $apiContact = $this->client->postContacts($email);
                    //apicontact found and can be removed using the contact id!
                    if (! isset($apiContact->message) && isset($apiContact->id)) {
                        //will assume that the request is done
                        $this->client->deleteContact($apiContact->id);
                    }
                    $this->_handleSingleItemAfterSync($item, $apiContact);
                }
            }
        }
    }

    /**
     * @param $item
     * @param $apiContact
     */
    public function _handleSingleItemAfterSync($item, $apiContact)
    {
        $curlError = $this->_checkCurlError($item);
        //no api connection error
        if (! $curlError) {
            //api response error
            if (isset($apiContact->message) or ! $apiContact) {
                $message = (isset($apiContact->message)) ? $apiContact->message : 'Error unknown';
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                    ->setMessage($message)
                    ->save();
            } else {
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::IMPORTED)
                    ->setImportFinished(time())
                    ->setImportStarted(time())
                    ->setMessage('')
                    ->save();
            }
        }
    }
}
