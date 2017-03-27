<?php

namespace Dotdigitalgroup\Email\Model\Sync\Td;

class Update extends \Dotdigitalgroup\Email\Model\Sync\Contact\Delete
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
                        $result = $this->client->postAccountTransactionalData(
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
                        $result = $this->client->postContactsTransactionalData(
                            $importData,
                            $item->getImportType()
                        );
                    }

                    $this->_handleSingleItemAfterSync($item, $result);
                }
            }
        }
    }
}
