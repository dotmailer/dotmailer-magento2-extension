<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData;

use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\SingleItemPostProcessorFactory;

/**
 * Handle TD update data for importer.
 */
class Update extends AbstractItemSyncer
{
    /**
     * @var SingleItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * Update constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
     * @param SingleItemPostProcessorFactory $postProcessor
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        SingleItemPostProcessorFactory $postProcessor,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;

        parent::__construct($helper, $fileHelper, $serializer, $importerResource, $data);
    }

    /**
     * Process.
     *
     * @param mixed $collection
     *
     * @return stdClass|null
     */
    public function process($item)
    {
        $importData = $this->serializer->unserialize($item->getImportData());

        if (strpos($item->getImportType(), 'Catalog_') !== false) {
            $result = $this->client->postAccountTransactionalData(
                $importData,
                $item->getImportType()
            );
        } elseif ($item->getImportType() == Importer::IMPORT_TYPE_CART_INSIGHT_CART_PHASE) {
            $result = $this->client->postAbandonedCartCartInsight(
                $importData
            );
        } else {
            $result = $this->client->postContactsTransactionalData(
                $importData,
                $item->getImportType()
            );
        }

        return $result;
    }
}
