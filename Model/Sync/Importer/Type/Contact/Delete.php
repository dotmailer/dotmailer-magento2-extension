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
 *
 * @deprecated Will be moved to a message queue.
 * @see \Dotdigitalgroup\Email\Model\Queue
 */
class Delete extends AbstractItemSyncer
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
     * @return \stdClass|null|string
     * @throws \Exception
     */
    public function process($item)
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
