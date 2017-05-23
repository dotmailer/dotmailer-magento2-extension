<?php

namespace Dotdigitalgroup\Email\Model\Sync\Td;

/**
 * Class Bulk
 * @package Dotdigitalgroup\Email\Model\Sync\Td
 */
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
                $importData = $this->serializer->unserialize($item->getImportData());

                if ($this->client) {
                    if (strpos($item->getImportType(), 'Catalog_') !== false) {
                        $result = $this->client->postAccountTransactionalDataImport(
                            $importData,
                            $item->getImportType()
                        );
                    } else {
                        if ($item->getImportType() == \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS) {
                            //Skip if one hour has not passed from created
                            if ($this->helper->getDateDifference($item->getCreatedAt()) < 3600) {
                                continue;
                            }
                        }
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
