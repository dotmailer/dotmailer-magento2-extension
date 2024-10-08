<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Logger\Logger;
use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;

class ImportResponseHandler
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Logger $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Logger $logger,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * Get import id from serialized JSON.
     *
     * @param string $response
     *
     * @return string
     */
    public function getImportIdFromResponse($response)
    {
        try {
            $responseData = $this->serializer->unserialize($response);
            return $responseData['importId'] ?? '';
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());
            return '';
        }
    }
}
