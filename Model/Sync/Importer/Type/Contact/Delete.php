<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\SingleItemPostProcessorFactory;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Handle delete data for importer.
 */
class Delete extends AbstractItemSyncer
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
     * @throws \Exception
     */
    public function process($item): ?\stdClass
    {
        $email = $this->serializer->unserialize($item->getImportData());
        $result = $this->client->postContacts($email);

        //apicontact found and can be removed using the contact id!
        if (! isset($result->message) && isset($result->id)) {
            //will assume that the request is done
            $this->client->deleteContact($result->id);
        }

        return $result;
    }
}
