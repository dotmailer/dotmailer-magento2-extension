<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Catalogvalues implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory::SYNC_CATALOG_DEFAULT_LEVEL,
                'label' => 'Default Level',
            ],
            [
                'value' => \Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory::SYNC_CATALOG_STORE_LEVEL,
                'label' => 'Store Level',
            ],
        ];
    }
}
