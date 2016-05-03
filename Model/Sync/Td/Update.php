<?php

namespace Dotdigitalgroup\Email\Model\Sync\Td;

use DotMailer\Api\DataTypes\ApiTransactionalData;

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

                $apiTransactionalData = new ApiTransactionalData();
                $apiTransactionalData->key = $importData->id;
                $apiTransactionalData->ContactIdentifier = $importData->email;
                $apiTransactionalData->Json = $importData->toJson();
                $collectionName= $item->getImportType();

                if (strpos($item->getImportType(), 'Catalog_') !== false) {
                    $apiTransactionalData->ContactIdentifier = $importData->email;
                }else {

                    $apiTransactionalData->ContactIdentifier = $importData->id;
                }
                $result = $this->_client->PostContactsTransactionalData($collectionName, $apiTransactionalData);

                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }
}