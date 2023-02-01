<?php

namespace Dotdigitalgroup\Email\Model\Sync\PendingContact;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\PendingContact\Type\TypeProviderInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class PendingContactUpdater
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var TypeProviderInterface
     */
    private $typeProvider;

    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $idsToUpdateStatus = [];

    /**
     * @var array
     */
    private $idsToUpdateDate = [];

    /**
     * @var array
     */
    private $idsToExpire = [];

    /**
     * Constructor.
     *
     * @param Data $helper
     * @param TimezoneInterface $timeZone
     * @param TypeProviderInterface $typeProvider
     * @param DateTime $dateTime
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        TimezoneInterface $timeZone,
        TypeProviderInterface $typeProvider,
        DateTime $dateTime,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->timeZone = $timeZone;
        $this->typeProvider = $typeProvider;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
    }

    /**
     * Update.
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function update()
    {
        $dateTimeFromDb = $this->getCollectionFactory()->create()
            ->getLastPendingStatusCheckTime();
        if (!$dateTimeFromDb) {
            return;
        }

        if ($this->isItTimeToCheckPendingContact($dateTimeFromDb)) {
            $this->checkStatusForPendingContacts(
                $this->getCollectionFactory()->create()
                    ->getCollectionByPendingStatus()
            );
            $this->updateRows(
                $this->getResourceModel()
            );
        }
    }

    /**
     * Check pending contact.
     *
     * Check if contact should have been updated based on time.
     *
     * @param string $dateTimeFromDb
     * @return bool
     */
    private function isItTimeToCheckPendingContact($dateTimeFromDb)
    {
        $lastCheckTime = $this->timeZone->date($dateTimeFromDb);
        $interval = new \DateInterval('PT30M');
        $lastCheckTime->add($interval);
        $now = $this->timeZone->date();
        return ($now->format('Y-m-d H:i:s') > $lastCheckTime->format('Y-m-d H:i:s'));
    }

    /**
     * Check status for pending contacts.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function checkStatusForPendingContacts($collection)
    {
        $expiryDate = $this->getDateTimeForExpiration();

        foreach ($collection as $item) {
            $websiteId = empty($item->getWebsiteId()) ?
                $this->getWebsiteIdFromStoreId($item->getStoreId()) :
                $item->getWebsiteId();
            $contact = $this->helper->getOrCreateContact($item->getEmail(), $websiteId);
            if (!$contact ||
                ($contact->status === StatusInterface::PENDING_OPT_IN &&
                    ($item->getCreatedAt() < $expiryDate))
            ) {
                $this->idsToExpire[] = $item->getId();
            } elseif (isset($contact->id) && $contact->status !== StatusInterface::PENDING_OPT_IN) {
                $this->idsToUpdateStatus[] = $item->getId();
            } else {
                $this->idsToUpdateDate[] = $item->getId();
            }
        }
    }

    /**
     * Get datetime for expiration.
     *
     * @return string
     */
    private function getDateTimeForExpiration()
    {
        $hours = $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_AC_AUTOMATION_EXPIRE_TIME
        );

        $interval = new \DateInterval(sprintf('PT%sH', $hours));

        $dateTime = $this->timeZone->date();
        $dateTime->sub($interval);

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * Update db rows.
     *
     * @param Abandoned|Automation $resourceModel
     */
    private function updateRows($resourceModel): void
    {
        $updatedAt = $this->dateTime->formatDate(true);

        $resourceModel
            ->update(
                $this->idsToUpdateStatus,
                $updatedAt,
                StatusInterface::CONFIRMED
            );

        $resourceModel
            ->update(
                $this->idsToUpdateDate,
                $updatedAt,
                StatusInterface::PENDING_OPT_IN
            );

        $resourceModel
            ->update(
                $this->idsToExpire,
                $updatedAt,
                StatusInterface::EXPIRED
            );
    }

    /**
     * Get websiteId from storeId.
     *
     * @param string|int $storeId
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getWebsiteIdFromStoreId($storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }

    /**
     * Get collection factory.
     *
     * @return mixed
     */
    private function getCollectionFactory()
    {
        return $this->typeProvider->getCollectionFactory();
    }

    /**
     * Get resource model.
     *
     * @return mixed
     */
    private function getResourceModel()
    {
        return $this->typeProvider->getResourceModel();
    }
}
