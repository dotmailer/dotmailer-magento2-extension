<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Review extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
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
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        $connectionName = null
    )
    {
        $this->quoteFactory = $quoteFactory;
        $this->reviewFactory = $reviewFactory;
        $this->productCollection = $productCollection;
        parent::__construct(
            $context,
            $connectionName
        );
    }

    /**
     * Get quote products to show feefo reviews.
     *
     * @param $quoteId
     * @return array
     */
    public function getQuoteProducts($quoteId)
    {
        $products = [];

        if($quoteId) {
            /** @var \Magento\Quote\Model\Quote $quoteModel */
            $quoteModel = $this->quoteFactory->create()
                ->load($quoteId);

            if (!$quoteModel->getId()) {
                return $products;
            }

            $quoteItems = $quoteModel->getAllItems();

            if (count($quoteItems) == 0) {
                return $products;
            }

            foreach ($quoteItems as $item) {
                $productModel = $item->getProduct();

                if ($productModel->getId()) {
                    $products[$productModel->getSku()] = $productModel->getName();
                }
            }
        }

        return $products;
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
}
