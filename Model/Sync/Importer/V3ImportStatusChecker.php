<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigital\V3\Models\Import\ImportInterface as V3ImportInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Importer\Context\InProgressImportContext;

/**
 * Polls the V3 API for the status of an in-progress import.
 *
 * Composed into the V3 in-progress import response handlers, replacing the
 * client resolution and status check previously inherited from
 * AbstractInProgressImportResponseHandler.
 */
class V3ImportStatusChecker
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Client[]
     */
    private $clients = [];

    /**
     * @param ClientFactory $clientFactory
     * @param Logger $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        Logger $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
    }

    /**
     * Get a V3 client for a website.
     *
     * @param int|string $websiteId
     * @return Client
     */
    public function getClient($websiteId): Client
    {
        $websiteId = (int) $websiteId;

        if (!isset($this->clients[$websiteId])) {
            $this->clients[$websiteId] = $this->clientFactory->create([
                'data' => [
                    'websiteId' => $websiteId
                ]
            ]);
        }

        return $this->clients[$websiteId];
    }

    /**
     * Check the import status of an importer row.
     *
     * @param ImporterModel $item
     * @param InProgressImportContext $context
     * @param string $logPrefix Optional prefix to identify the caller in error logs.
     * @return V3ImportInterface
     * @throws \Exception
     */
    public function check(
        ImporterModel $item,
        InProgressImportContext $context,
        string $logPrefix = ''
    ): V3ImportInterface {
        $method = $context->getMethod();
        $resource = $context->getResource();

        if (!$resource) {
            throw new \InvalidArgumentException('V3 import status check requires a resource name');
        }

        try {
            return $this->getClient($item->getWebsiteId())
                ->$resource
                ->$method($item->getImportId());
        } catch (ResponseValidationException $e) {
            $this->logger->error(
                sprintf(
                    '%sChecking import id %s: %s - %s',
                    $logPrefix === '' ? '' : $logPrefix . ' ',
                    $item->getImportId(),
                    $e->getCode(),
                    $e->getMessage()
                ),
                [$e->getDetails()]
            );
            throw new \Exception($e->getMessage());
        }
    }
}
