<?php

namespace Dotdigitalgroup\Email\Model\Sync\Td;

class Bulk extends \Dotdigitalgroup\Email\Model\Sync\Contact\Bulk
{
    /**
     * Sync.
     *
     * @param $collection
     */
    public function sync($collection)
    {
        foreach ($collection as $item) {
            $websiteId = $item->getWebsiteId();
            if ($this->_helper->isEnabled($websiteId)) {
                $this->_client = $this->_helper->getWebsiteApiClient($websiteId);
                $importData = unserialize($item->getImportData());

                if ($this->_client) {
                    if (strpos($item->getImportType(), 'Catalog_') !== false) {
                        $result = $this->_client->postAccountTransactionalDataImport(
                            $importData,
                            $item->getImportType()
                        );
                    } else {
                        $result = $this->_client->postContactsTransactionalDataImport(
                            $importData,
                            $item->getImportType()
                        );
                    }
                    $this->_handleItemAfterSync($item, $result);
                }
            }
        }
    }
}
