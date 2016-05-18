<?php

namespace Dotdigitalgroup\Email\Model\Sync\Td;

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
                if (strpos($item->getImportType(), 'Catalog_') !== false) {
                    $result = $this->_client->postContactsTransactionalData(
                        $importData,
                        $item->getImportType(),
                        true
                    );
                } else {
                    $result = $this->_client->postContactsTransactionalData(
                        $importData,
                        $item->getImportType()
                    );
                }

                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }
}