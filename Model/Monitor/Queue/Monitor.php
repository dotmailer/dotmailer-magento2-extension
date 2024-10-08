<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Queue;

use Dotdigitalgroup\Email\Model\Monitor\AbstractMonitor;
use Dotdigitalgroup\Email\Model\Monitor\MonitorInterface;
use Magento\MysqlMq\Model\QueueManagement;
use Magento\MysqlMq\Model\ResourceModel\MessageStatusCollection;
use Magento\MysqlMq\Model\ResourceModel\MessageStatusCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use Magento\MysqlMq\Model\MessageStatus;

class Monitor extends AbstractMonitor implements MonitorInterface
{
    public const MONITOR_ERROR_FLAG_CODE = 'ddg_monitor_queue_errors';

    /**
     * @var MessageStatusCollectionFactory
     */
    private $messageStatusCollectionFactory;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = self::MONITOR_ERROR_FLAG_CODE;

    /**
     * @var string
     */
    protected $typeName = 'queue';

    /**
     * Monitor constructor.
     *
     * @param FlagManager $flagManager
     * @param ScopeConfigInterface $scopeConfig
     * @param MessageStatusCollectionFactory $messageStatusCollectionFactory
     */
    public function __construct(
        FlagManager $flagManager,
        ScopeConfigInterface $scopeConfig,
        MessageStatusCollectionFactory $messageStatusCollectionFactory
    ) {
        $this->flagManager = $flagManager;
        $this->scopeConfig = $scopeConfig;
        $this->messageStatusCollectionFactory = $messageStatusCollectionFactory;
        parent::__construct($flagManager, $scopeConfig);
    }

    /**
     * Fetch errors for the given time window.
     *
     * @param array $timeWindow
     * @return array
     */
    public function fetchErrors(array $timeWindow): array
    {
        $possiblyConcerningMessages = $this->fetchPossiblyConcerningMessages($timeWindow);
        $grouped = $this->groupItemsIntoPendingAndErrorArrays($possiblyConcerningMessages->getItems());

        $errors = $grouped['items']['error'];
        $pending = $grouped['items']['pending'];
        $grouped['totalRecords'] = count($errors) + count($pending);

        return (!empty($errors) || !empty($pending)) ?
            $grouped :
            [
                'totalRecords' => 0,
                'items' => []
            ];
    }

    /**
     * Filter the error items.
     *
     * @param array $items
     * @return array
     */
    public function filterErrorItems(array $items)
    {
        $filtered = [];
        foreach ($items as $type => $group) {
            $topicsForType = [];
            foreach ($group as $object) {
                if (!in_array($object->getTopicName(), $topicsForType)) {
                    $topicsForType[] = $object->getTopicName();
                }
            }
            $filtered[$type] = implode(', ', $topicsForType);
        }

        return $filtered;
    }

    /**
     * Fetch messages in new or pending status.
     *
     * @param array $timeWindow
     *
     * @return MessageStatusCollection
     */
    private function fetchPossiblyConcerningMessages(array $timeWindow): MessageStatusCollection
    {
        $messageCollection = $this->messageStatusCollectionFactory->create();

        $messageCollection->join(
            ['queue_message' => $messageCollection->getTable('queue_message')],
            'main_table.message_id = queue_message.id',
            ['topic_name']
        );

        $messageCollection->addFieldToFilter('status', [
            'in' => [
                QueueManagement::MESSAGE_STATUS_NEW,
                QueueManagement::MESSAGE_STATUS_ERROR
            ]])
            ->addFieldToFilter('topic_name', ['like' => "%ddg%"])
            ->addFieldToFilter('updated_at', $timeWindow);

        return $messageCollection;
    }

    /**
     * Group items into pending and error arrays.
     *
     * @param array<MessageStatus> $items
     *
     * @return array
     */
    private function groupItemsIntoPendingAndErrorArrays(array $items)
    {
        $errors = $pending = [];
        foreach ($items as $item) {
            if ((int) $item->getStatus() === QueueManagement::MESSAGE_STATUS_ERROR) {
                $errors[] = $item;
            } elseif ((int) $item->getStatus() === QueueManagement::MESSAGE_STATUS_NEW) {
                if ($item->getUpdatedAt() > strtotime('-1 hour')) {
                    $pending[] = $item;
                }
            }
        }

        return [
            'items' => [
                'error' => $errors,
                'pending' => $pending
            ]
        ];
    }
}
