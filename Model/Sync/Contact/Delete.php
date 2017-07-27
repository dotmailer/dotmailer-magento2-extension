<?php

namespace Dotdigitalgroup\Email\Model\Sync\Contact;

/**
 * Handle delete data for importer.
 */
class Delete extends \Dotdigitalgroup\Email\Model\Sync\Contact\Bulk
{
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
            $email = $this->serializer->unserialize($item->getImportData());

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
     * @param mixed $item
     * @param mixed $apiContact
     *
     * @return null
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
                    ->setMessage($message);
            } else {
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::IMPORTED)
                    ->setImportFinished(time())
                    ->setImportStarted(time())
                    ->setMessage('');
            }
            $this->importerResource->save($item);
        }
    }
}
