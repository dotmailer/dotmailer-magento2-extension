<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Review\Model\ResourceModel\Rating\Option;

class Review extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    private $mageReviewCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;

    /**
     * @var Option\Vote\CollectionFactory
     */
    private $voteCollection;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * Initialize resource.
     *
     * @return void
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
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $mageReviewCollectionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param Option\Vote\CollectionFactory $voteCollection
     * @param ?string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $mageReviewCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory $voteCollection,
        $connectionName = null
    ) {
        $this->helper = $data;
        $this->mageReviewCollectionFactory = $mageReviewCollectionFactory;
        $this->productFactory = $productFactory;
        $this->voteCollection = $voteCollection;
        $this->productCollectionFactory = $productCollectionFactory;
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
                'review_imported = ?' => 1
            ];
        } else {
            $where = ['review_imported = ?' => 1];
        }
        $num = $conn->update(
            $this->getTable(Schema::EMAIL_REVIEW_TABLE),
            ['review_imported' => 0],
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

            $collection = $this->mageReviewCollectionFactory->create()
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

        if (!empty($productIds)) {
            $products = $this->productCollectionFactory->create()
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
     * @return void
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
        $reviews = $this->mageReviewCollectionFactory->create()
            ->addFieldToFilter(
                'main_table.review_id',
                ['in' => $ids]
            )
            ->addFieldToFilter('customer_id', ['notnull' => 'true']);

        $reviews->getSelect()
            ->joinLeft(
                ['c' => $this->getTable('customer_entity')],
                'c.entity_id = customer_id',
                ['email']
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
        $product = $this->productCollectionFactory->create()
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

    /**
     * Get store id from review.
     *
     * @param string $reviewId
     * @return string
     */
    public function getStoreIdFromReview($reviewId)
    {
        $storeId = '0';

        $collection = $this->mageReviewCollectionFactory->create()
            ->addStoreData()
            ->addFieldToFilter('main_table.review_id', $reviewId)
            ->getFirstItem();

        foreach ($collection->getStores() as $store) {
            if ($store !== '0') {
                return $store;
            }
        }

        return $storeId;
    }
}
