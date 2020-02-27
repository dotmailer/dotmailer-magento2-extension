<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact;

use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\SingleItemPostProcessorFactory;

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
