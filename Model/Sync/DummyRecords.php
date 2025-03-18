<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigital\V3\Models\ContactFactory as DotdigitalContactFactory;
use Dotdigital\V3\Models\InsightData;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\DummyRecordsData;

class DummyRecords implements SyncInterface
{
    /**
     * @var DotdigitalContactFactory
     */
    private $sdkContactFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var DummyRecordsData
     */
    private $dummyData;

    /**
     * DummyRecords constructor.
     *
     * @param DotdigitalContactFactory $sdkContactFactory
     * @param Logger $logger
     * @param DummyRecordsData $dummyData
     * @param ClientFactory $clientFactory
     */
    public function __construct(
        DotdigitalContactFactory $sdkContactFactory,
        Logger $logger,
        DummyRecordsData $dummyData,
        ClientFactory $clientFactory
    ) {
        $this->sdkContactFactory = $sdkContactFactory;
        $this->logger = $logger;
        $this->dummyData = $dummyData;
        $this->clientFactory = $clientFactory;
    }

    /**
     * Sync.
     *
     * @param \DateTime $from
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return void
     */
    public function sync(?\DateTime $from = null)
    {
        foreach ($this->dummyData->getActiveWebsites() as $websiteId) {
            $this->postContactAndCartInsightData($websiteId);
        }
    }

    /**
     * Sync for website.
     *
     * @param string|int $websiteId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function syncForWebsite($websiteId)
    {
        $this->postContactAndCartInsightData($websiteId);
    }

    /**
     * Post contact and cartInsight data.
     *
     * @param string|int $websiteId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    private function postContactAndCartInsightData($websiteId = 0)
    {
        $identifier = $this->dummyData->getEmailFromAccountInfo($websiteId);
        $client = $this->clientFactory->create(
            ['data' => ['websiteId' => $websiteId]]
        );

        try {
            $contact = $this->sdkContactFactory->create();
            $contact->setMatchIdentifier('email');
            $contact->setIdentifiers(['email' => $identifier]);
            $client->contacts->patchByIdentifier($identifier, $contact);
        } catch (ResponseValidationException $e) {
            if (strpos($e->getMessage(), 'identifierConflict') === false) {
                $this->logger->debug(
                    sprintf(
                        '%s: %s',
                        'Could not create new contact to attach dummy cart insight',
                        $e->getMessage()
                    ),
                    [$e->getDetails()]
                );
            }
        }

        try {
            $cartInsightData = new InsightData([
                'collectionName' => 'CartInsight',
                'collectionScope' => 'contact',
                'collectionType' => 'cartInsight'
            ]);
            $cartInsightData->setRecords([
                $this->dummyData->getContactInsightData($websiteId)
            ]);
            $client->insightData->import($cartInsightData);
        } catch (ResponseValidationException $e) {
            $this->logger->debug(
                sprintf(
                    '%s: %s',
                    'Could not send dummy cart insight data',
                    $e->getMessage()
                ),
                [$e->getDetails()]
            );
        }
    }
}
