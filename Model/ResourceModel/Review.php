<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Review extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    public $mageReviewCollection;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;
    /**
     * @var \Magento\Review\Model\Rating\Option\Vote
     */
    public $vote;

    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_review', 'id');
    }

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $mageReviewCollection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Review\Model\Rating\Option\Vote $vote
    ) {
        $this->helper = $data;
        $this->mageReviewCollection = $mageReviewCollection;
        $this->productFactory = $productFactory;
        $this->vote = $vote;
        parent::__construct($context);
    }

    /**
     * Reset the email reviews for re-import.
     *
     * @param null $from
     * @param null $to
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetReviews($from = null, $to = null)
    {
        $conn = $this->getConnection();
        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'review_imported is ?' => new \Zend_Db_Expr('not null')
            ];
        } else {
            $where = $conn->quoteInto(
                'review_imported is ?',
                new \Zend_Db_Expr('not null')
            );
        }
        try {
            $num = $conn->update(
                $conn->getTableName('email_review'),
                ['review_imported' => new \Zend_Db_Expr('null')],
                $where
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }

    /**
     * Set imported in bulk query.
     *
     * @param $ids
     * @param $nowDate
     */
    public function setImported($ids, $nowDate)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $coreResource->getTableName('email_review');
            $ids = implode(', ', $ids);
            $coreResource->update(
                $tableName,
                ['review_imported' => 1, 'updated_at' => $nowDate],
                ["review_id IN (?)" => $ids]
            );
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }

    /**
     * Get Mage reviews by ids
     *
     * @param $ids
     * @return mixed
     */
    public function getMageReviewsByIds($ids)
    {
        $reviews = $this->mageReviewCollection->create()
            ->addFieldToFilter(
                'main_table.review_id', ['in' => $ids]
            )
            ->addFieldToFilter('customer_id', ['notnull' => 'true']);

        $reviews->getSelect()
            ->joinLeft(
                ['c' => $this->getConnection()->getTableName('customer_entity')],
                'c.entity_id = customer_id',
                ['email', 'store_id']
            );

        return $reviews;
    }

    /**
     * Get product by id and store
     *
     * @param $id
     * @param $storeId
     * @return mixed
     */
    public function getProductByIdAndStore($id, $storeId)
    {
        $product = $this->productFactory->create()
            ->getCollection()
            ->addIdFilter($id)
            ->setStoreId($storeId)
            ->addAttributeToSelect(
                ['product_url', 'name', 'store_id', 'small_image']
            )
            ->setPage(1, 1);

        //@codingStandardsIgnoreStart
        $product->getFirstItem();
        //@codingStandardsIgnoreEnd

        return $product;
    }

    /**
     * Get vote collection by review
     *
     * @param $reviewId
     * @return mixed
     */
    public function getVoteCollectionByReview($reviewId)
    {
        $votesCollection = $this->vote
            ->getResourceCollection()
            ->setReviewFilter($reviewId);

        $votesCollection->getSelect()->join(
            ['rating' => 'rating'],
            'rating.rating_id = main_table.rating_id',
            ['rating_code' => 'rating.rating_code']
        );

        return $votesCollection;
    }
}
