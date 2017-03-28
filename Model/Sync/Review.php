<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Review
{
    /**
     * @var
     */
    public $start;
    /**
     * @var
     */
    public $reviews;
    /**
     * @var
     */
    public $countReviews;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;

    /**
     * @var
     */
    public $reviewIds;
    /**
     * @var \Magento\Review\Model\ReviewFactory
     */
    public $reviewFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Customer\ReviewFactory
     */
    public $connectorReviewFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Customer\Review\RatingFactory
     */
    public $ratingFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Review\CollectionFactory
     */
    public $reviewCollection;
    /**
     * @var \Magento\Review\Model\Rating\Option\Vote
     */
    public $vote;
    /**
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    public $mageReviewCollection;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $coreDate;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $coreResource;

    /**
     * Review constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Review\CollectionFactory $reviewCollection
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                         $coreDate
     * @param \Dotdigitalgroup\Email\Model\Customer\Review\RatingFactory          $ratingFactory
     * @param \Dotdigitalgroup\Email\Model\Customer\ReviewFactory                 $connectorFactory
     * @param \Magento\Customer\Model\CustomerFactory                             $customerFactory
     * @param \Magento\Catalog\Model\ProductFactory                               $productFactory
     * @param \Magento\Framework\App\ResourceConnection                           $resourceConnection
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory                        $importerFactory
     * @param \Magento\Review\Model\ReviewFactory                                 $reviewFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                                  $data
     * @param \Magento\Framework\App\ResourceConnection                           $resource
     * @param \Magento\Review\Model\Rating\Option\Vote                            $vote
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory        $mageReviewCollection
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Review\CollectionFactory $reviewCollection,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Dotdigitalgroup\Email\Model\Customer\Review\RatingFactory $ratingFactory,
        \Dotdigitalgroup\Email\Model\Customer\ReviewFactory $connectorFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Review\Model\Rating\Option\Vote $vote,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $mageReviewCollection
    ) {

        $this->coreResource           = $resourceConnection;
        $this->coreDate               = $coreDate;
        $this->reviewCollection       = $reviewCollection;
        $this->ratingFactory          = $ratingFactory;
        $this->connectorReviewFactory = $connectorFactory;
        $this->customerFactory        = $customerFactory;
        $this->productFactory         = $productFactory;
        $this->reviewFactory          = $reviewFactory;
        $this->importerFactory        = $importerFactory;
        $this->helper                 = $data;
        $this->resource               = $resource;
        $this->vote                   = $vote;
        $this->mageReviewCollection  = $mageReviewCollection;
    }

    /**
     * Sync reviews.
     *
     * @return array
     */
    public function sync()
    {
        $response = ['success' => true, 'message' => 'Done.'];

        $this->countReviews = 0;
        $this->reviews      = [];
        $this->start        = microtime(true);
        $websites           = $this->helper->getwebsites(true);
        foreach ($websites as $website) {
            $apiEnabled = $this->helper->isEnabled($website);
            $reviewEnabled = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_REVIEW_ENABLED,
                $website
            );
            $storeIds = $website->getStoreIds();
            if ($apiEnabled && $reviewEnabled && !empty($storeIds)) {
                $this->_exportReviewsForWebsite($website);
            }

            if (isset($this->reviews[$website->getId()])) {
                $reviews = $this->reviews[$website->getId()];
                //send reviews as transactional data
                //register in queue with importer
                $this->importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_REVIEWS,
                        $reviews,
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $website->getId()
                    );
                //if no error then set imported
                $this->_setImported($this->reviewIds);
                //@codingStandardsIgnoreStart
                $this->countReviews += count($reviews);
                //@codingStandardsIgnoreStop
            }
        }

        if ($this->countReviews) {
            $message = '----------- Review sync ----------- : ' . gmdate('H:i:s', microtime(true) - $this->start) .
                ', synced = ' . $this->countReviews;
            $this->helper->log($message);
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Export reviews for website.
     *
     * @param \Magento\Store\Model\Website $website
     */
    public function _exportReviewsForWebsite(\Magento\Store\Model\Website $website)
    {
        $limit           = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $emailReviews    = $this->_getReviewsToExport($website, $limit);
        $this->reviewIds = [];

        if ($emailReviews->getSize()) {
            $reviews = $this->mageReviewCollection->create()
                ->addFieldToFilter(
                    'main_table.review_id', ['in' => $emailReviews->getColumnValues('review_id')]
                )
                ->addFieldToFilter('customer_id', ['notnull' => 'true']);

            $reviews->getSelect()
                ->joinLeft(
                    ['c' => $this->coreResource->getTableName('customer_entity')],
                    'c.entity_id = customer_id',
                    ['email', 'store_id']
                );
            foreach ($reviews as $mageReview) {
                try {
                    $product = $this->productFactory->create()
                        ->getCollection()
                        ->addIdFilter($mageReview->getEntityPkValue())
                        ->setStoreId($mageReview->getStoreId())
                        ->addAttributeToSelect(
                            ['product_url', 'name', 'store_id', 'small_image']
                        )
                        ->setPage(1, 1)
                        ->getFirstItem();

                    $connectorReview = $this->connectorReviewFactory->create()
                        ->setReviewData($mageReview)
                        ->setProduct($product);

                    $votesCollection = $this->vote
                        ->getResourceCollection()
                        ->setReviewFilter($mageReview->getReviewId());
                    $votesCollection->getSelect()->join(
                        ['rating' => 'rating'],
                        'rating.rating_id = main_table.rating_id',
                        ['rating_code' => 'rating.rating_code']
                    );

                    foreach ($votesCollection as $ratingItem) {
                        $rating = $this->ratingFactory->create()
                            ->setRating($ratingItem)
                        ;
                        $connectorReview->createRating(
                            $ratingItem->getRatingCode(), $rating
                        );
                    }
                    $this->reviews[$website->getId()][] = $connectorReview->expose();
                    $this->reviewIds[]                  = $mageReview->getReviewId();
                } catch (\Exception $e) {
                    $this->helper->debug((string)$e, []);
                }
            }
        }
    }

    /**
     * Get reviews for export.
     *
     * @param \Magento\Store\Model\Website $website
     * @param int $limit
     *
     * @return mixed
     */
    public function _getReviewsToExport(\Magento\Store\Model\Website $website, $limit = 100)
    {
        return $this->reviewCollection->create()
            ->addFieldToFilter('review_imported', ['null' => 'true'])
            ->addFieldToFilter(
                'store_id', ['in' => $website->getStoreIds()]
            )
            ->setPageSize($limit);
    }

    /**
     * Set imported in bulk query.
     *
     * @param $ids
     */
    public function _setImported($ids)
    {
        try {
            $coreResource = $this->resource;
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('email_review');
            $ids = implode(', ', $ids);
            $nowDate = $this->coreDate->gmtDate();
            $write->update(
                $tableName,
                ['review_imported' => 1, 'updated_at' => $nowDate],
                "review_id IN ($ids)"
            );
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
