<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Ui\DataProvider\Queue\Listing;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param string $identifierName
     * @param string $connectionName
     * @throws \Magento\Framework\Exception\LocalizedException
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'queue_message',
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    /**
     * Init select.
     *
     * This is the init query to join the two queue tables.
     *
     * @return void
     */
    protected function _initSelect(): void
    {
        parent::_initSelect();
        $this->addFieldToSelect(['topic_name'])->getSelect()->join(
            ['queue_message_status' => $this->getTable('queue_message_status')],
            'main_table.id = queue_message_status.message_id',
            ['status', 'number_of_trials', 'message_id', 'updated_at']
        );
    }

    /**
     * Load message by message id.
     *
     * @param string $messageId
     * @return Collection
     */
    public function loadMessageById(string $messageId): Collection
    {
        return $this->addFieldToSelect('body')
        ->addFieldToFilter('main_table.id', $messageId);
    }
}
