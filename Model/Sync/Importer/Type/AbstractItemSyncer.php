<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type;

use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\ItemPostProcessorInterfaceFactory;
use Magento\Framework\DataObject;

abstract class AbstractItemSyncer extends DataObject
{
    /**
     * Legendary error message
     */
    const ERROR_UNKNOWN = 'Error unknown';

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $helper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    protected $fileHelper;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer
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
     * AbstractItemSyncer constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
     * @param \Dotdigitalgroup\Email\Model\Sync\Importer\Type\ItemPostProcessorInterfaceFactory $postProcessor
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->fileHelper = $fileHelper;
        $this->serializer = $serializer;
        $this->importerResource = $importerResource;

        parent::__construct($data);
    }

    /**
     * @param $collection
     */
    public function sync($collection)
    {
        $this->client = $this->getClient();

        foreach ($collection as $item) {
            $result = $this->process($item);
            $this->postProcessor->create(['data' => ['client' => $this->client]])
                ->handleItemAfterSync($item, $result);
        }
    }

    abstract protected function process($item);

    /**
     * @return Client
     */
    private function getClient()
    {
        return $this->_getData('client');
    }
}
