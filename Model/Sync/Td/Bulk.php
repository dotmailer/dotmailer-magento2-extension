<?php

namespace Dotdigitalgroup\Email\Model\Sync\Td;

use DotMailer\Api\DataTypes\ApiContact;
use DotMailer\Api\DataTypes\ApiFileMedia;
use DotMailer\Api\DataTypes\ApiTransactionalDataList;
use DotMailer\Api\DataTypes\ApiTransactionalData;
use Symfony\Component\Config\Definition\Exception\Exception;

class Bulk extends \Dotdigitalgroup\Email\Model\Sync\Contact\Bulk
{

    public function sync($collection)
    {
        foreach ($collection as $item) {
            $websiteId      = $item->getWebsiteId();
            $collectionName = $item->getImportType();
            $this->_client  = $this->_helper->getWebsiteApiClient($websiteId);
            $importData     = unserialize($item->getImportData());

            if ($this->_client) {

                if (strpos($collectionName, 'Catalog_') !== false) {

                    $data = array();
                    foreach ($importData as $one) {
                        if (isset($one->id)) {
                            $data[] = array(
                                'Key'               => $one->id,
                                'ContactIdentifier' => 'account',
                                'Json'              => json_encode(
                                    $one->expose()
                                )
                            );
                        }
                    }
                    $apiData = new ApiTransactionalDataList($data);
                    $result
                             = $this->_client->PostContactsTransactionalDataImport(
                        'Catalog_Default', $apiData
                    );

                    $this->_handleItemAfterSync($item, $result);
                } else {

                    $apiData = new ApiTransactionalDataList($importData);

                    $result
                        = $this->_client->PostContactsTransactionalDataImport(
                        $collectionName, $apiData
                    );
                    $this->_handleItemAfterSync($item, $result);
                }
            }
        }
    }
}