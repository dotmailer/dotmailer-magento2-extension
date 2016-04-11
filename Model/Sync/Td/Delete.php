<?php
namespace Dotdigitalgroup\Email\Model\Sync\Td;

class Delete extends \Dotdigitalgroup\Email\Model\Sync\Contact\Delete
{
    

    protected function _processCollection($collection)
    {
        foreach($collection as $item)
        {
            $websiteId = $item->getWebsiteId();
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
            $importData = unserialize($item->getImportData());

            if ($this->_client) {
                $result = $this->_client->deleteContactsTransactionalData($importData[0], $item->getImportType());
                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }
}