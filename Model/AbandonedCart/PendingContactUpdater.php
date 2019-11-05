<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart;

use Dotdigitalgroup\Email\Model\Sales\Quote;
use Dotdigitalgroup\Email\Model\Sync\Automation;

class PendingContactUpdater
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory
     */
    private $abandonedCollectionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timeZone;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned
     */
    private $abandonedResource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory $abandonedCollectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $abandonedResource
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory $abandonedCollectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $abandonedResource,
        \Magento\Framework\Stdlib\DateTime $dateTime
    ) {
        $this->abandonedCollectionFactory = $abandonedCollectionFactory;
        $this->helper                     = $helper;
        $this->dateIntervalFactory        = $dateIntervalFactory;
        $this->timeZone                   = $timeZone;
        $this->abandonedResource          = $abandonedResource;
        $this->dateTime                   = $dateTime;
    }

    /**
     * @return void
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function update()
    {
        if ($this->itIsTimeToCheckPendingContact()) {
            $this->checkStatusForPendingContactsInternal();
        }
    }

    /**
     * @return boolean
     */
    private function itIsTimeToCheckPendingContact()
    {
        $dateTimeFromDb = $this->abandonedCollectionFactory->create()->getLastPendingStatusCheckTime();
        if (!$dateTimeFromDb) {
            return false;
        }

        $lastCheckTime = $this->timeZone->date($dateTimeFromDb);
        $interval       = $this->dateIntervalFactory->create(['interval_spec' => 'PT30M']);
        $lastCheckTime->add($interval);
        $now = $this->timeZone->date();
        return ($now->format('Y-m-d H:i:s') > $lastCheckTime->format('Y-m-d H:i:s'));
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function checkStatusForPendingContactsInternal()
    {
        $updatedAt         = $this->dateTime->formatDate(true);
        $expiryDate        = $this->getDateTimeForExpiration();
        $collection        = $this->abandonedCollectionFactory->create()
                                                              ->getCollectionByPendingStatus();
        $idsToUpdateStatus = [];
        $idsToUpdateDate   = [];
        $idsToExpire       = [];
        foreach ($collection as $item) {
            $websiteId = $this->helper->storeManager->getStore($item->getStoreId())->getWebsiteId();
            $contact   = $this->helper->getOrCreateContact($item->getEmail(), $websiteId);
            if (isset($contact->id) && $contact->status !== Automation::CONTACT_STATUS_PENDING) {
                $idsToUpdateStatus[] = $item->getId();
            } elseif (($item->getCreatedAt() < $expiryDate) &&
                      $contact->status === Automation::CONTACT_STATUS_PENDING
            ) {
                $idsToExpire[] = $item->getId();
            } else {
                $idsToUpdateDate[] = $item->getId();
            }
        }

        $this->updateCarts($idsToUpdateStatus, $idsToUpdateDate, $idsToExpire, $updatedAt);
    }

    /**
     * @return string
     */
    private function getDateTimeForExpiration()
    {
        $hours    = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AC_AUTOMATION_EXPIRE_TIME
        );
        $interval = $this->dateIntervalFactory->create(
            ['interval_spec' => sprintf('PT%sH', $hours)]
        );

        $dateTime = $this->timeZone->date();
        $dateTime->sub($interval);

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @param int[] $idsToUpdateStatus
     * @param int[] $idsToUpdateDate
     * @param int[] $idsToExpire
     * @param string $updatedAt
     */
    private function updateCarts($idsToUpdateStatus, $idsToUpdateDate, $idsToExpire, $updatedAt)
    {
        $this->abandonedResource
            ->update(
                $idsToUpdateStatus,
                $updatedAt,
                Quote::STATUS_CONFIRMED
            );

        $this->abandonedResource
            ->update(
                $idsToUpdateDate,
                $updatedAt,
                Quote::STATUS_PENDING
            );

        $this->abandonedResource
            ->update(
                $idsToExpire,
                $updatedAt,
                Quote::STATUS_EXPIRED
            );
    }
}
