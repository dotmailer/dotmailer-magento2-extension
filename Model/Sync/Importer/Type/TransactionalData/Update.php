<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer as ModelImporter;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\SingleItemPostProcessorFactory;
use Magento\Framework\Serialize\SerializerInterface;

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
     * @param Data $helper
     * @param File $fileHelper
     * @param SerializerInterface $serializer
     * @param Importer $importerResource
     * @param SingleItemPostProcessorFactory $postProcessor
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Data $helper,
        File $fileHelper,
        SerializerInterface $serializer,
        Importer $importerResource,
        SingleItemPostProcessorFactory $postProcessor,
        Logger $logger,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;

        parent::__construct($helper, $fileHelper, $serializer, $importerResource, $logger, $data);
    }

    /**
     * Process.
     *
     * @param mixed $item
     * @return \stdClass|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($item): ?\stdClass
    {
        $importData = $this->serializer->unserialize($item->getImportData());

        if (strpos($item->getImportType(), 'Catalog_') !== false) {
            $result = $this->client->postAccountTransactionalData(
                $importData,
                $item->getImportType()
            );
        } elseif ($item->getImportType() == ModelImporter::IMPORT_TYPE_CART_INSIGHT_CART_PHASE) {
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
