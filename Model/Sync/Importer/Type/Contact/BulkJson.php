<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\SenderStrategyFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\V3ItemPostProcessorFactory;
use Exception;
use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;

class BulkJson extends AbstractItemSyncer
{
    /**
     * @var SenderStrategyFactory
     */
    private $senderStrategyFactory;

    /**
     * @var V3ItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param V3ItemPostProcessorFactory $postProcessor
     * @param SerializerInterface $serializer
     * @param SenderStrategyFactory $senderStrategyFactory
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        V3ItemPostProcessorFactory $postProcessor,
        SerializerInterface        $serializer,
        SenderStrategyFactory      $senderStrategyFactory,
        Logger                     $logger,
        array                      $data = []
    ) {
        $this->postProcessor = $postProcessor;
        $this->serializer = $serializer;
        $this->senderStrategyFactory = $senderStrategyFactory;
        parent::__construct($logger, $data);
    }

    /**
     * Process.
     *
     * @param ImporterModel $item
     *
     * @return string
     * @throws InvalidArgumentException|ResponseValidationException|Exception
     */
    public function process($item)
    {
        $importData = $this->serializer->unserialize($item->getImportData());

        foreach ($importData as $key => $data) {
            $contact = new SdkContact($data);
            $importData[$key] = $contact;
        }

        return $this->senderStrategyFactory->create($item->getImportType())
            ->setBatch($importData)
            ->setWebsiteId((int)$item->getWebsiteId())
            ->process();
    }
}
