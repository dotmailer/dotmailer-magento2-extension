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
            $result = true;
            $websiteId = $item->getWebsiteId();
            //@codingStandardsIgnoreStart
            $email = unserialize($item->getImportData());
            //@codingStandardsIgnoreEnd
            if ($this->helper->isEnabled($websiteId)) {
                $this->client = $this->helper->getWebsiteApiClient($websiteId);

                if ($this->client) {
                    $apiContact = $this->client->postContacts($email);
                    if (!isset($apiContact->message) && isset($apiContact->id)) {
                        $result = $this->client->deleteContact($apiContact->id);
                    } elseif (isset($apiContact->message) && !isset($apiContact->id)) {
                        $result = $apiContact;
                    }

                    if ($result) {
                        $this->_handleSingleItemAfterSync($item, $result);
                    }
                }
            }
        }
    }

    /**
     * @param $item
     * @param $result
     */
    public function _handleSingleItemAfterSync($item, $result)
    {
        $curlError = $this->_checkCurlError($item);

        if (!$curlError) {
            if (isset($result->message) or !$result) {
                $message = (isset($result->message)) ? $result->message : 'Error unknown';
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
