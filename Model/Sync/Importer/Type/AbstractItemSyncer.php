<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection;
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
            } catch (\InvalidArgumentException|\Exception $e) {
                $this->logger->error(
                    sprintf(
                        'Error processing %s import data for id %s',
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
     * @param ImporterModel $item
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
