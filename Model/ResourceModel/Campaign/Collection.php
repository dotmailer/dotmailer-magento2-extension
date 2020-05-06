<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Campaign;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var \Dotdigitalgroup\Email\Model\DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->dateIntervalFactory = $dateIntervalFactory;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Initialize resource collection.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Campaign::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Campaign::class
        );
    }

    /**
     * Get campaign by quote id.
     *
     * @param int $quoteId
     * @param int $storeId
     *
     * @return \Dotdigitalgroup\Email\Model\Campaign|boolean
     */
    public function loadByQuoteId($quoteId, $storeId)
    {
        $collection = $this->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('store_id', $storeId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Get campaign collection.
     *
     * @param array $storeIds
     * @param int $sendStatus
     * @param bool $sendIdCheck
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\Collection
     */
    public function getEmailCampaignsByStoreIds($storeIds, $sendStatus = 0, $sendIdCheck = false)
    {
        $campaignCollection = $this->addFieldToFilter('send_status', $sendStatus)
            ->addFieldToFilter('campaign_id', ['notnull' => true])
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        //check for send id
        if ($sendIdCheck) {
            $campaignCollection->addFieldToFilter('send_id', ['notnull' => true])
                ->getSelect()
                ->group('send_id');
        } else {
            $campaignCollection->getSelect()
                ->order('campaign_id');
        }

        $campaignCollection->getSelect()
            ->limit(\Dotdigitalgroup\Email\Model\Sync\Campaign::SEND_EMAIL_CONTACT_LIMIT);

        return $campaignCollection;
    }

    /**
     * Get expired campaigns by store ids
     *
     * @param array $storeIds
     * @return Collection
     */
    public function getExpiredEmailCampaignsByStoreIds($storeIds)
    {
        $time = new \DateTime('now', new \DateTimezone('UTC'));
        $interval = $this->dateIntervalFactory->create(
            ['interval_spec' => sprintf('PT%sH', 2)]
        );
        $time->sub($interval);

        $campaignCollection = $this->addFieldToFilter('campaign_id', ['notnull' => true])
            ->addFieldToFilter('send_status', \Dotdigitalgroup\Email\Model\Campaign::PROCESSING)
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->addFieldToFilter('send_id', ['notnull' => true])
            ->addFieldToFilter('updated_at', ['lt' => $time->format('Y-m-d H:i:s')]);

        return $campaignCollection;
    }

    /**
     * Get collection by event.
     *
     * @param string $event
     *
     * @return $this
     */
    public function getCollectionByEvent($event)
    {
        return $this->addFieldToFilter('event_name', $event);
    }

    /**
     * Get number of campaigns for contact by interval.
     *
     * @param string  $email
     * @param array $updated
     *
     * @return int
     */
    public function getNumberOfCampaignsForContactByInterval($email, $updated)
    {
        return $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('event_name', 'Lost Basket')
            ->addFieldToFilter('sent_at', $updated)
            ->count();
    }

    /**
     * @param string $email
     *
     * @return int
     */
    public function getNumberOfAcCampaignsWithStatusProcessingExistForContact($email)
    {
        return $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('event_name', \Dotdigitalgroup\Email\Model\Campaign::CAMPAIGN_EVENT_LOST_BASKET)
            ->addFieldToFilter('send_status', \Dotdigitalgroup\Email\Model\Campaign::PROCESSING)
            ->getSize();
    }

    /**
     * Search the email_campaign table for jobs with sent_status = '3'(failed),
     * with a created_at time inside the specified time window.
     * @param $timeWindow
     * @return Collection
     */
    public function fetchCampaignsWithErrorStatusInTimeWindow($timeWindow)
    {
        return $this->addFieldToFilter('send_status', \Dotdigitalgroup\Email\Model\Campaign::FAILED)
            ->addFieldToFilter('created_at', $timeWindow)
            ->setOrder('updated_at', 'DESC');
    }
}
