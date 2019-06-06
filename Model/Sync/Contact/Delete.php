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
                    $this->handleSingleItemAfterSync($item, $apiContact);
                }
            }
        }
    }
}
