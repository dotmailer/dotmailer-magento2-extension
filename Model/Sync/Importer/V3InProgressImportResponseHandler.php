<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Sync\Importer\ReportHandler\V3ImporterReportHandler;
use Dotdigital\V3\Models\Contact\Import as SdkImport;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;

class V3InProgressImportResponseHandler extends AbstractInProgressImportResponseHandler
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var V3ImporterReportHandler
     */
    private $reportHandler;

    /**
     * @param Logger $logger
     * @param ClientFactory $clientFactory
     * @param ImporterResource $importerResource
     * @param V3ImporterReportHandler $reportHandler
     */
    public function __construct(
        Logger $logger,
        ClientFactory $clientFactory,
        ImporterResource $importerResource,
        V3ImporterReportHandler $reportHandler
    ) {
        $this->reportHandler = $reportHandler;
        $this->clientFactory = $clientFactory;
        parent::__construct($logger, $importerResource);
    }

    /**
     * Check item import status.
     *
     * @param ImporterModel $item
     * @param array $group
     *
     * @return SdkImport
     */
    protected function checkItemImportStatus(
        ImporterModel $item,
        array $group
    ) :SdkImport {
        $method = $group['method'];
        $resource = $group['resource'];

        return $this->getClient($item->getWebsiteId())
            ->$resource
            ->$method(
                $item->getImportId()
            );
    }

    /**
     * Process Response.
     *
     * @param SdkImport $response
     * @param ImporterModel $item
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processResponse($response, $item)
    {
        $itemCount = 0;
        if ($response->getStatus() == 'Finished') {
            $item = $this->processFinishedItem($item);
        } elseif (in_array($response->getStatus(), self::$importStatuses)) {
            $item->setImportStatus(ImporterModel::FAILED)
                ->setMessage('Import failed with status ' . $response->getStatus());
        } else {
            //Not finished
            $itemCount = 1;
        }

        $this->reportHandler->process($response);
        $this->importerResource->save($item);

        return $itemCount;
    }

    /**
     * @inheritdoc
     */
    protected function getClient($websiteId)
    {
        if (!isset($this->client)) {
            $this->client = $this->clientFactory->create([
                'data' => [
                    'websiteId' => $websiteId
                ]
            ]);
        }
        return $this->client;
    }
}
