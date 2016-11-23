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
            if ($this->helper->isEnabled($websiteId)) {
                $this->client = $this->helper->getWebsiteApiClient($websiteId);
                //@codingStandardsIgnoreStart
                $importData = unserialize($item->getImportData());
                //@codingStandardsIgnoreEnd

                if ($this->client) {
                    if (strpos($item->getImportType(), 'Catalog_') !== false) {
                        $result = $this->client->postAccountTransactionalDataImport(
                            $importData,
                            $item->getImportType()
                        );
                    } else {
                        $result = $this->client->postContactsTransactionalDataImport(
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
