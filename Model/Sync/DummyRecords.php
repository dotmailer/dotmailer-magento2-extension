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
     * @param $email
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function postContactAndCartInsightData($websiteId)
    {
        $this->helper->getWebsiteApiClient($websiteId)
            ->postAbandonedCartCartInsight(
                $this->dummyData->getContactInsightData($websiteId)
            );
    }
}
