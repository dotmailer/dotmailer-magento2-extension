<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Review;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init(
            'Dotdigitalgroup\Email\Model\Review',
            'Dotdigitalgroup\Email\Model\ResourceModel\Review'
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
        return $this->addFieldToFilter('review_imported', ['null' => 'true'])
            ->addFieldToFilter(
                'store_id', ['in' => $website->getStoreIds()]
            )
            ->setPageSize($limit);
    }
}
