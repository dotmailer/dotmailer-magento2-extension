<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer as ModelImporter;
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Update constructor.
     *
     * @param SerializerInterface $serializer
     * @param SingleItemPostProcessorFactory $postProcessor
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        SerializerInterface $serializer,
        SingleItemPostProcessorFactory $postProcessor,
        Logger $logger,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;
        $this->serializer = $serializer;

        parent::__construct($logger, $data);
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
