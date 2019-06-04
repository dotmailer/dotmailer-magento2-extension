<?php

namespace Dotdigitalgroup\Email\Model\Sync\Td;

/**
 * Handle TD update data for importer.
 */
class Update extends \Dotdigitalgroup\Email\Model\Sync\Contact\Delete
{
    /**
     * Sync.
     *
     * @param mixed $collection
     *
     * @return null
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
                        $result = $this->client->postAccountTransactionalData(
                            $importData,
                            $item->getImportType()
                        );
                    } elseif ($item->getImportType() == \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CART_INSIGHT_CART_PHASE) {
                        $result = $this->client->postAbandonedCartCartInsight(
                            $importData
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

                    $this->handleSingleItemAfterSync($item, $result);
                }
            }
        }
    }
}
