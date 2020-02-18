<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Review;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Review::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Review::class
        );
    }

    /**
     * Get reviews for export.
     *
     * @param \Magento\Store\Model\Website $website
     * @param int $limit
     *
     * @return $this
     */
    public function getReviewsToExportByWebsite(\Magento\Store\Model\Website $website, $limit = 100)
    {
        return $this->addFieldToFilter('review_imported', 0)
            ->addFieldToFilter(
                'store_id',
                ['in' => $website->getStoreIds()]
            )
            ->setPageSize($limit);
    }
}
