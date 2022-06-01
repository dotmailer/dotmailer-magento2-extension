<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\DummyRecordsData;
use Dotdigitalgroup\Email\Logger\Logger;

class DummyRecords implements SyncInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var DummyRecordsData
     */
    private $dummyData;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * DummyRecords constructor.
     *
     * @param DummyRecordsData $dummyData
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(DummyRecordsData $dummyData, Data $helper, Logger $logger)
    {
        $this->dummyData = $dummyData;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Sync.
     *
     * Run in default level.
     *
     * @param \DateTime $from
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return void
     */
    public function sync(\DateTime $from = null)
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
        $cartInsightData = $this->dummyData->getContactInsightData($websiteId);
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $client->postContacts($cartInsightData['contactIdentifier']);
        $client->postAbandonedCartCartInsight($cartInsightData);
    }
}
