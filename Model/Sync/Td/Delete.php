<?php

namespace Dotdigitalgroup\Email\Model\Sync\Td;

/**
 * Class Delete
 * @package Dotdigitalgroup\Email\Model\Sync\Td
 */
class Delete extends \Dotdigitalgroup\Email\Model\Sync\Contact\Delete
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
            if ($this->helper->isEnabled($websiteId)) {
                $this->client = $this->helper->getWebsiteApiClient($websiteId);
                $importData = $this->serializer->unserialize($item->getImportData());

                if ($this->client) {
                    $key = $importData[0];
                    $collectionName = $item->getImportType();
                    $this->client->deleteContactsTransactionalData($key, $collectionName);
                    $this->_handleSingleItemAfterSync($item, $result);
                }
            }
        }
    }
}
