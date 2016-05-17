<?php
namespace Dotdigitalgroup\Email\Model\Sync\Td;

class Delete extends \Dotdigitalgroup\Email\Model\Sync\Contact\Delete
{


    public function sync($collection)
    {
        foreach($collection as $item)
        {
            $result = true;
            $websiteId = $item->getWebsiteId();
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
            $importData = unserialize($item->getImportData());

            if ($this->_client) {
                $key = $importData[0];
                $collectionName = $item->getImportType();
                $this->_client->deleteContactsTransactionalData($key, $collectionName);
                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }
}