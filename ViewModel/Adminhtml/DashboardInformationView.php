<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel\Adminhtml;

use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\MessageQueue\DefaultValueProvider;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class DashboardInformationView implements ArgumentInterface
{
    /**
     * @var DefaultValueProvider
     */
    private $defaultValueProvider;

    /**
     * @var ScheduleCollectionFactory
     */
    private $scheduleCollectionFactory;

    /**
     * @param DefaultValueProvider $defaultValueProvider
     * @param ScheduleCollectionFactory $scheduleCollectionFactory
     */
    public function __construct(
        DefaultValueProvider $defaultValueProvider,
        ScheduleCollectionFactory $scheduleCollectionFactory
    ) {
        $this->defaultValueProvider = $defaultValueProvider;
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
    }

    /**
     * Get the last successful execution for a given cron job.
     *
     * @param string $jobCode
     *
     * @return string
     */
    public function getCronLastExecution(string $jobCode)
    {
        $collection = $this->scheduleCollectionFactory->create()
            ->addFieldToFilter('status', Schedule::STATUS_SUCCESS)
            ->addFieldToFilter('job_code', $jobCode);

        $collection->getSelect()
            ->limit(1)
            ->order('executed_at DESC');

        return ($collection->getSize() == 0) ?
            '<span class="message message-error">No cron found</span>' :
            $collection->getFirstItem()->getExecutedAt();
    }

    /**
     * Get queue connection.
     *
     * @return string
     */
    public function getQueueConnection(): string
    {
        return $this->defaultValueProvider->getConnection();
    }
}
