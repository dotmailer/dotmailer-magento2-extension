<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Importer;

use Dotdigitalgroup\Email\Model\DateIntervalFactory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * Initialize resource collection.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Importer::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Importer::class
        );
    }

    /**
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param DateIntervalFactory $dateIntervalFactory
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        DateIntervalFactory $dateIntervalFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->dateIntervalFactory = $dateIntervalFactory;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    /**
     * Reset collection.
     *
     * @return void
     */
    public function reset()
    {
        $this->_reset();
    }

    /**
     * Get imports marked as importing for one or more websites.
     *
     * @param int $limit
     * @param array $websiteIds
     *
     * @return $this|boolean
     */
    public function getItemsWithImportingStatus($websiteIds)
    {
        $collection = $this->addFieldToFilter(
            'import_status',
            ['eq' => \Dotdigitalgroup\Email\Model\Importer::IMPORTING]
        )
            ->addFieldToFilter('import_id', ['neq' => ''])
            ->addFieldToFilter(
                'website_id',
                ['in' => $websiteIds]
            );

        if ($collection->getSize()) {
            return $collection;
        }

        return false;
    }

    /**
     * Get the imports by type and mode.
     *
     * @param string|array $importType
     * @param string $importMode
     * @param int $limit
     * @param array $websiteIds
     *
     * @return $this
     */
    public function getQueueByTypeAndMode($importType, $importMode, $limit, $websiteIds)
    {
        if (is_array($importType)) {
            $condition = [];
            foreach ($importType as $type) {
                if ($type == 'Catalog') {
                    $condition[] = ['like' => $type . '%'];
                } else {
                    $condition[] = ['eq' => $type];
                }
            }
            $this->addFieldToFilter('import_type', $condition);
        } else {
            $this->addFieldToFilter(
                'import_type',
                ['eq' => $importType]
            );

            /**
             * Skip orders if one hour has not passed since the created_at time.
             */
            if ($importType == 'Orders') {
                $interval = $this->dateIntervalFactory->create(
                    ['interval_spec' => 'PT1H']
                );
                $fromDate = new \DateTime('now', new \DateTimezone('UTC'));
                $fromDate->sub($interval);

                $this->addFieldToFilter(
                    'created_at',
                    ['lt' => $fromDate]
                );
            }
        }

        $this->addFieldToFilter('import_mode', ['eq' => $importMode])
            ->addFieldToFilter(
                'import_status',
                ['eq' => \Dotdigitalgroup\Email\Model\Importer::NOT_IMPORTED]
            );

        $this->addFieldToFilter('website_id', ['in' => $websiteIds]);

        $this->setPageSize($limit)
            ->setCurPage(1);

        return $this;
    }
}
