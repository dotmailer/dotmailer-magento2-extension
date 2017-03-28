<?php

namespace Dotdigitalgroup\Email\Model\Sync\Td;

class Bulk extends \Dotdigitalgroup\Email\Model\Sync\Contact\Bulk
{
    /**
     * Bulk constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
    ) {
    
        parent::__construct($helper, $fileHelper, $contactFactory);
    }

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
