<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\Schema;
use Magento\Review\Model\ResourceModel\Rating\Option;

/**
 * Class Review
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
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
     * @var Option\Vote\CollectionFactory
     */
    private $voteCollection;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Magento\Review\Model\ReviewFactory
     */
    private $reviewFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollection;

    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_REVIEW_TABLE, 'id');
    }

    /**
     * Review constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $mageReviewCollection
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param Option\Vote\CollectionFactory $voteCollection
     * @param \Magento\Review\Model\Rating\Option\Vote $vote
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $mageReviewCollection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory $voteCollection,
        \Magento\Review\Model\Rating\Option\Vote $vote,
        $connectionName = null
    ) {
        $this->helper = $data;
        $this->mageReviewCollection = $mageReviewCollection;
        $this->productFactory = $productFactory;
        $this->vote = $vote;
        $this->quoteFactory = $quoteFactory;
        $this->reviewFactory = $reviewFactory;
        $this->voteCollection = $voteCollection;
        $this->productCollection = $productCollectionFactory;
        parent::__construct(
            $context,
            $connectionName
        );
    }

    /**
     * Reset the email reviews for re-import.
     *
     * @param string $from
     * @param string $to
     *
     * @return int
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
        $num = $conn->update(
            $this->getTable(Schema::EMAIL_REVIEW_TABLE),
            ['review_imported' => new \Zend_Db_Expr('null')],
            $where
        );

        return $num;
    }

    /**
     * Filter items for review.
     *
     * @param array $items
     * @param int $customerId
     * @param \Magento\Sales\Model\Order $order
     *
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
     * Get product collection from order.
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return array|\Magento\Framework\Data\Collection\AbstractDb
     */
    public function getProductCollection($quote)
    {
        $productIds = [];
        $products = [];
        $items = $quote->getAllVisibleItems();

        //get the product ids for the collection
        foreach ($items as $item) {
            $productIds[] = $item->getProductId();
        }

        if (! empty($productIds)) {
            $products = $this->productCollection->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('entity_id', ['in' => $productIds]);
        }

        return $products;
    }

    /**
     * Set imported in bulk query.
     *
     * @param array $ids
     * @param string $nowDate
     *
     * @return null
     */
    public function setImported($ids, $nowDate)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $this->getTable(Schema::EMAIL_REVIEW_TABLE);
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
     * Get Mage reviews by ids.
     *
     * @param array $ids
     *
     * @return \Magento\Review\Model\ResourceModel\Review\Collection
     */
    public function getMageReviewsByIds($ids)
    {
        $reviews = $this->mageReviewCollection->create()
            ->addFieldToFilter(
                'main_table.review_id',
                ['in' => $ids]
            )
            ->addFieldToFilter('customer_id', ['notnull' => 'true']);

        $reviews->getSelect()
            ->joinLeft(
                ['c' => $this->getTable('customer_entity')],
                'c.entity_id = customer_id',
                ['email', 'store_id']
            );

        return $reviews;
    }

    /**
     * Get product by id and store.
     *
     * @param int $id
     * @param int $storeId
     *
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

        return $product->getFirstItem();
    }

    /**
     * Get vote collection by review.
     *
     * @param int $reviewId
     *
     * @return mixed
     */
    public function getVoteCollectionByReview($reviewId)
    {
        $votesCollection = $this->voteCollection->create()
            ->setReviewFilter($reviewId);

        $votesCollection->getSelect()->join(
            ['rating' => $this->getTable('rating')],
            'rating.rating_id = main_table.rating_id',
            ['rating_code' => 'rating.rating_code']
        );

        return $votesCollection;
    }
}
