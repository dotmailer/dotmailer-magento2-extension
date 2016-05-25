<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Review
{
    protected $_start;
    protected $_reviews;
    protected $_countReviews;
    protected $_helper;
    protected $_resource;
    protected $_vote;
    protected $_reviewIds;
    protected $_reviewFactory;
    protected $_importerFactory;
    protected $_productFactory;
    protected $_customerFactory;
    protected $_connectorReviewFactory;
    protected $_ratingFactory;
    protected $_reviewCollection;

    /**
     * Review constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Resource\Review\CollectionFactory $reviewCollection
     * @param \Dotdigitalgroup\Email\Model\Customer\Review\RatingFactory     $ratingFactory
     * @param \Dotdigitalgroup\Email\Model\Customer\ReviewFactory            $connectorFactory
     * @param \Magento\Customer\Model\CustomerFactory                        $customerFactory
     * @param \Magento\Catalog\Model\ProductFactory                          $productFactory
     * @param \Magento\Review\Model\ReviewFactory                            $reviewFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                             $data
     * @param \Magento\Framework\App\ResourceConnection                      $resource
     * @param \Magento\Framework\Stdlib\Datetime                             $datetime
     * @param \Magento\Review\Model\Rating\Option\Vote $vote
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Resource\Review\CollectionFactory $reviewCollection,
        \Dotdigitalgroup\Email\Model\Customer\Review\RatingFactory $ratingFactory,
        \Dotdigitalgroup\Email\Model\Customer\ReviewFactory $connectorFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\Datetime $datetime,
        \Magento\Review\Model\Rating\Option\Vote $vote
    ) {
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
        $this->_vote = $vote;
    }

    public function sync()
    {
        $response = array('success' => true, 'message' => 'Done.');

        $this->_countReviews = 0;
        $this->_reviews = array();
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
                $this->_countReviews += count($reviews);
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

    protected function _exportReviewsForWebsite($website)
    {
        $limit = $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
            $website
        );
        $reviews = $this->_getReviewsToExport($website, $limit);
        $this->_reviewIds = array();

        if ($reviews->getSize()) {
            foreach ($reviews as $review) {
                try {
                    $mageReview = $this->_reviewFactory->create()
                        ->load($review->getReviewId());
                    $product = $this->_productFactory->create()
                        ->setStoreId($mageReview->getStoreId())
                        ->load($mageReview->getEntityPkValue());

                    $customer = $this->_customerFactory->create()
                        ->load($mageReview->getCustomerId());

                    $connectorReview = $this->_connectorReviewFactory->create()
                        ->setCustomer($customer)
                        ->setReviewData($mageReview)
                        ->setProduct($product);

                    $votesCollection = $this->_vote
                        ->getResourceCollection()
                        ->setReviewFilter($mageReview->getReviewId());
                    $votesCollection->getSelect()->join(
                        array('rating' => 'rating'),
                        'rating.rating_id = main_table.rating_id',
                        array('rating_code' => 'rating.rating_code')
                    );

                    foreach ($votesCollection as $ratingItem) {
                        $rating = $this->_ratingFactory->create()
                            ->setRating($ratingItem);
                        $connectorReview->createRating(
                            $ratingItem->getRatingCode(), $rating
                        );
                    }
                    $this->_reviews[$website->getId()][] = $connectorReview;
                    $this->_reviewIds[]
                                                         = $review->getReviewId();
                } catch (\Exception $e) {
                    $this->_helper->debug((string)$e, array());
                }
            }
        }
    }

    protected function _getReviewsToExport($website, $limit = 100)
    {
        return $this->_reviewCollection->create()
            ->addFieldToFilter('review_imported', array('null' => 'true'))
            ->addFieldToFilter(
                'store_id', array('in' => $website->getStoreIds())
            )
            ->setPageSize($limit);
    }

    /**
     * set imported in bulk query.
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
            $now = new \Datetime();
            $nowDate = $this->_dateTime->formatDate($now->getTimestamp());
            $write->update(
                $tableName,
                array('review_imported' => 1, 'updated_at' => $nowDate),
                "review_id IN ($ids)"
            );
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }
    }
}
