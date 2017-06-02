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
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;
    /**
     * @var \Magento\Review\Model\ReviewFactory
     */
    private $reviewFactory;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private $productCollection;

    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_review', 'id');
    }

    /**
     * Review constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $mageReviewCollection
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Review\Model\Rating\Option\Vote $vote
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $mageReviewCollection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Review\Model\Rating\Option\Vote $vote,
        $connectionName = null
    ) {
        $this->helper = $data;
        $this->mageReviewCollection = $mageReviewCollection;
        $this->productFactory = $productFactory;
        $this->vote = $vote;
        $this->quoteFactory = $quoteFactory;
        $this->reviewFactory = $reviewFactory;
        $this->productCollection = $productCollection;
        parent::__construct(
            $context,
            $connectionName
        );
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
     * Filter items for review
     *
     * @param $items
     * @param $customerId
     * @param $order
     * @return mixed
     */
    public function filterItemsForReview($items, $customerId, $order)
    {
        foreach ($items as $key => $item) {
            $productId = $item->getProduct()->getId();

            $collection = $this->reviewFactory->create()->getCollection()
                ->addCustomerFilter($customerId)
                ->addStoreFilter($order->getStoreId())
                ->addFieldToFilter('main_table.entity_pk_value', $productId);

            //remove item if customer has already placed review on this item
            if ($collection->getSize()) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    /**
     * Get product collection from order
     *
     * @param $order
     * @return array|\Magento\Framework\Data\Collection\AbstractDb
     */
    public function getProductCollection($order)
    {
        $productIds = [];
        $products = [];
        $items = $order->getAllVisibleItems();

        //get the product ids for the collection
        foreach ($items as $item) {
            $productIds[] = $item->getProductId();
        }

        if (! empty($productIds)) {
            $products = $this->productCollection
                ->addAttributeToSelect('*')
                ->addFieldToFilter('entity_id', ['in' => $productIds]);
        }

        return $products;
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

        $product->getFirstItem();

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
