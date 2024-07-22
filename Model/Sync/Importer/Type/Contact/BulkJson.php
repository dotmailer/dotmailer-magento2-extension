<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\SendContactDataStrategy;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\SendDataStrategyHandler;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\V3ItemPostProcessorFactory;
use Exception;
use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;

class BulkJson extends AbstractItemSyncer
{
    /**
     * @var SendContactDataStrategy
     */
    private $sendContactDataStrategy;

    /**
     * @var SendDataStrategyHandler
     */
    private $sendDataStrategyHandler;

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
     * @param SendContactDataStrategy $sendContactDataStrategy
     * @param SendDataStrategyHandler $sendDataStrategyHandler
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        V3ItemPostProcessorFactory $postProcessor,
        SerializerInterface $serializer,
        SendContactDataStrategy $sendContactDataStrategy,
        SendDataStrategyHandler $sendDataStrategyHandler,
        Logger $logger,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;
        $this->serializer = $serializer;
        $this->sendDataStrategyHandler = $sendDataStrategyHandler;
        $this->sendContactDataStrategy = $sendContactDataStrategy;
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

        $this->sendDataStrategyHandler->setStrategy($this->sendContactDataStrategy);
        return $this->sendDataStrategyHandler->executeStrategy($importData, (int) $item->getWebsiteId());
    }
}
