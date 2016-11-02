<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Review
{
    /**
     * @var
     */
    protected $_start;
    /**
     * @var
     */
    protected $_reviews;
    /**
     * @var
     */
    protected $_countReviews;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var
     */
    protected $_reviewIds;
    /**
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $_reviewFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Customer\ReviewFactory
     */
    protected $_connectorReviewFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Customer\Review\RatingFactory
     */
    protected $_ratingFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Review\CollectionFactory
     */
    protected $_reviewCollection;
    /**
     * @var \Magento\Review\Model\Rating\Option\Vote
     */
    protected $vote;
    /**
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    protected $_mageReviewCollection;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_coreDate;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_coreResource;

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
     * @param \Magento\Framework\Stdlib\DateTime                                  $datetime
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
        \Magento\Framework\Stdlib\DateTime $datetime,
        \Magento\Review\Model\Rating\Option\Vote $vote,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $mageReviewCollection
    ) {
        $this->_coreResource = $resourceConnection;
        $this->_coreDate = $coreDate;
        $this->_reviewCollection = $reviewCollection;
        $this->_ratingFactory = $ratingFactory;
        $this->_connectorReviewFactory = $connectorFactory;
        $this->_customerFactory = $customerFactory;
        $this->_productFactory = $productFactory;
        $this->_reviewFactory = $reviewFactory;
        $this->_importerFactory = $importerFactory;
        $this->_helper = $data;
        $this->_resource = $resource;
        $this->_dateTime = $datetime;
        $this->vote = $vote;
        $this->_mageReviewCollection = $mageReviewCollection;
    }

    /**
     * Sync reviews.
     *
     * @return array
     */
    public function sync()
    {
        $response = ['success' => true, 'message' => 'Done.'];

        $this->_countReviews = 0;
        $this->_reviews = [];
        $this->_start = microtime(true);
        //resource allocation
        $this->_helper->allowResourceFullExecution();
        $websites = $this->_helper->getwebsites(true);
        foreach ($websites as $website) {
            $apiEnabled = $this->_helper->isEnabled($website);
            $reviewEnabled = $this->_helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_REVIEW_ENABLED,
                $website
            );
            $storeIds = $website->getStoreIds();
            if ($apiEnabled && $reviewEnabled && !empty($storeIds)) {
                //start the sync
                if (!$this->_countReviews) {
                    $this->_helper->log(
                        '---------- Start reviews sync ----------'
                    );
                }
                $this->_exportReviewsForWebsite($website);
            }

            if (isset($this->_reviews[$website->getId()])) {
                $reviews = $this->_reviews[$website->getId()];
                //send reviews as transactional data
                //register in queue with importer
                $this->_importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_REVIEWS,
                        $reviews,
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $website->getId()
                    );
                //if no error then set imported
                $this->_setImported($this->_reviewIds);
                //@codingStandardsIgnoreStart
                $this->_countReviews += count($reviews);
                //@codingStandardsIgnoreStop
            }
        }

        if ($this->_countReviews) {
            $message = 'Total time for sync : ' . gmdate(
                    'H:i:s', microtime(true) - $this->_start
                ) . ', Total synced = ' . $this->_countReviews;
            $this->_helper->log($message);
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Export reviews for website.
     *
     * @param \Magento\Store\Model\Website $website
     */
    protected function _exportReviewsForWebsite(\Magento\Store\Model\Website $website)
    {
        $limit = $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $emailReviews = $this->_getReviewsToExport($website, $limit);
        $this->_reviewIds = [];

        if ($emailReviews->getSize()) {
            $reviews = $this->_mageReviewCollection->create()
                ->addFieldToFilter(
                    'main_table.review_id', ['in' => $emailReviews->getColumnValues('review_id')]
                )
                ->addFieldToFilter('customer_id', ['notnull' => 'true']);

            $reviews->getSelect()
                ->joinLeft(
                    ['c' => $this->_coreResource->getTableName('customer_entity')],
                    'c.entity_id = customer_id',
                    ['email', 'store_id']
                );
            foreach ($reviews as $mageReview) {
                try {
                    $product = $this->_productFactory->create()
                        ->getCollection()
                        ->addIdFilter($mageReview->getEntityPkValue())
                        ->setStoreId($mageReview->getStoreId())
                        ->addAttributeToSelect(
                            ['product_url', 'name', 'store_id', 'small_image']
                        )
                        ->setPage(1, 1)
                        ->getFirstItem();

                    $connectorReview = $this->_connectorReviewFactory->create()
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
                        $rating = $this->_ratingFactory->create()
                            ->setRating($ratingItem)
                        ;
                        $connectorReview->createRating(
                            $ratingItem->getRatingCode(), $rating
                        );
                    }
                    $this->_reviews[$website->getId()][] = $connectorReview;
                    $this->_reviewIds[] = $mageReview->getReviewId();
                } catch (\Exception $e) {
                    $this->_helper->debug((string)$e, []);
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
    protected function _getReviewsToExport(\Magento\Store\Model\Website $website, $limit = 100)
    {
        return $this->_reviewCollection->create()
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
    protected function _setImported($ids)
    {
        try {
            $coreResource = $this->_resource;
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('email_review');
            $ids = implode(', ', $ids);
            $nowDate = $this->_coreDate->gmtDate();
            $write->update(
                $tableName,
                ['review_imported' => 1, 'updated_at' => $nowDate],
                "review_id IN ($ids)"
            );
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, []);
        }
    }
}
