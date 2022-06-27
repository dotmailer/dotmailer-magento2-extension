<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\ItemPostProcessorInterfaceFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\SerializerInterface;

abstract class AbstractItemSyncer extends DataObject
{
    /**
     * Legendary error message
     */
    public const ERROR_UNKNOWN = 'Error unknown';

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var File
     */
    protected $fileHelper;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Importer
     */
    protected $importerResource;

    /**
     * @var ItemPostProcessorInterfaceFactory
     */
    protected $postProcessor;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * AbstractItemSyncer constructor.
     * @param Data $helper
     * @param File $fileHelper
     * @param SerializerInterface $serializer
     * @param Importer $importerResource
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Data $helper,
        File $fileHelper,
        SerializerInterface $serializer,
        Importer $importerResource,
        Logger $logger,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->fileHelper = $fileHelper;
        $this->serializer = $serializer;
        $this->importerResource = $importerResource;
        $this->logger = $logger;

        parent::__construct($data);
    }

    /**
     * Run Sync(s)
     *
     * @param mixed $collection
     * @return void
     */
    public function sync($collection)
    {
        $result = null;
        $this->client = $this->getClient();
        foreach ($collection as $item) {
            try {
                $result = $this->process($item);
            } catch (\InvalidArgumentException $e) {
                $this->logger->debug(
                    sprintf(
                        'Error processing %s import data for ID: %d',
                        $item->getImportType(),
                        $item->getImportId()
                    ),
                    [(string)$e]
                );
            }
            $this->postProcessor
                ->create(['data' => ['client' => $this->client]])
                ->handleItemAfterSync($item, $result);
        }
    }

    /**
     * Process sync
     *
     * @param mixed $item
     * @return mixed
     */
    abstract protected function process($item);

    /**
     * Get client for sync.
     *
     * @return Client
     */
    protected function getClient()
    {
        return $this->_getData('client');
    }
}
