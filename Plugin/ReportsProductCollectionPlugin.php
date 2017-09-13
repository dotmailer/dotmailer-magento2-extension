<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * Class ReportsProductCollection
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ReportsProductCollectionPlugin
{
    /**
     * @var \Magento\Reports\Model\Event\TypeFactory
     */
    private $eventTypeFactory;

    /**
     * ReportsProductCollection constructor.
     *
     * @param \Magento\Reports\Model\Event\TypeFactory $typeFactory
     */
    public function __construct(\Magento\Reports\Model\Event\TypeFactory $typeFactory)
    {
        $this->eventTypeFactory = $typeFactory;
    }

    /**
     * @param \Magento\Reports\Model\ResourceModel\Product\Collection $collection
     * @param callable $proceed
     * @param string $from
     * @param string $to
     *
     * @return \Magento\Reports\Model\ResourceModel\Product\Collection
     */
    public function aroundAddViewsCount(
        \Magento\Reports\Model\ResourceModel\Product\Collection $collection,
        callable $proceed,
        $from = '',
        $to = ''
    ) {
        /**
         * Getting event type id for catalog_product_view event
         */
        $eventTypes = $this->eventTypeFactory->create()->getCollection();
        foreach ($eventTypes as $eventType) {
            if ($eventType->getEventName() == 'catalog_product_view') {
                $productViewEvent = (int)$eventType->getId();
                break;
            }
        }

        $collection->getSelect()->reset()->from(
            ['report_table_views' => $collection->getTable('report_event')],
            ['views' => 'COUNT(report_table_views.event_id)']
        )->join(
            ['e' => $collection->getProductEntityTableName()],
            'e.entity_id = report_table_views.object_id'
        )->where(
            'report_table_views.event_type_id = ?',
            $productViewEvent
        )->group(
            'e.entity_id'
        )->order(
            'views ' . $collection::SORT_ORDER_DESC
        )->having(
            'COUNT(report_table_views.event_id) > ?',
            0
        );

        if ($from != '' && $to != '') {
            $collection->getSelect()->where('logged_at >= ?', $from)->where('logged_at <= ?', $to);
        }
        
        return $collection;
    }
}
