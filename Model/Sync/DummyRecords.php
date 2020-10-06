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
     * @param \DateTime|null $from
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return void
     */
    public function sync(\DateTime $from = null)
    {
        foreach ($this->dummyData->getDummyContactData() as $websiteId => $contact) {
            $this->postContactAndCartInsightData($websiteId);
        }
    }

    /**
     * @param $websiteId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function syncForWebsite($websiteId)
    {
        $this->postContactAndCartInsightData($websiteId);
    }

    /**
     * @param $websiteId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    private function postContactAndCartInsightData($websiteId)
    {
        $cartInsightData = $this->dummyData->getContactInsightData($websiteId);
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $client->postContacts($cartInsightData['contactIdentifier']);
        $client->postAbandonedCartCartInsight($cartInsightData);
    }
}
