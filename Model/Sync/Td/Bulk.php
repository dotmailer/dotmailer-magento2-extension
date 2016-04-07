<?php

namespace Dotdigitalgroup\Email\Model\Sync\Td;

class Bulk extends \Dotdigitalgroup\Email\Model\Sync\Contact\Bulk
{
    public function __construct($collection)
    {
        parent::__construct($collection);
    }

    protected function _processCollection($collection)
    {
        foreach($collection as $item)
        {
            $websiteId = $item->getWebsiteId();
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
            $importData = unserialize($item->getImportData());

            if ($this->_client) {
                if (strpos($item->getImportType(), 'Catalog_') !== false) {
                    $result = $this->_client->postAccountTransactionalDataImport($importData, $item->getImportType());
                    $this->_handleItemAfterSync($item, $result);
                }else {
                    $result = $this->_client->postContactsTransactionalDataImport($importData, $item->getImportType());
                    $this->_handleItemAfterSync($item, $result);
                }
            }
        }
    }
}