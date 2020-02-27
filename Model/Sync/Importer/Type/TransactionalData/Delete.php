<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData;

use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\SingleItemPostProcessorFactory;

/**
 * Handle TD delete data for importer.
 */
class Delete extends AbstractItemSyncer
{
    /**
     * @var SingleItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * Delete constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
     * @param SingleItemPostProcessorFactory $singlePostProcessor
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
     * @param mixed $item
     *
     * @return null
     */
    public function process($item)
    {
        $importData = $this->serializer->unserialize($item->getImportData());
        $key = $importData[0];
        $this->client->deleteContactsTransactionalData($key, $item->getImportType());
    }
}
