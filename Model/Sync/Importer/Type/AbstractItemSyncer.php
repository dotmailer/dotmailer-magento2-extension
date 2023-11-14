<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\ItemPostProcessorInterfaceFactory;
use Magento\Framework\DataObject;

abstract class AbstractItemSyncer extends DataObject
{
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
     *
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        array $data = []
    ) {
        $this->logger = $logger;

        parent::__construct($data);
    }

    /**
     * Run Sync(s).
     *
     * @param Collection $collection
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
     * Process sync.
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
