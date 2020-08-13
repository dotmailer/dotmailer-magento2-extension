<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Dotdigitalgroup\Email\Model\Sales\OrderFactory;

/**
 * Sync Reviews.
 */
class Review implements SyncInterface
{
    /**
     * @var mixed
     */
    private $start;

    /**
     * @var array
     */
    private $reviews;

    /**
     * @var int
     */
    private $countReviews;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var array
     */
    private $reviewIds;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Customer\ReviewFactory
     */
    private $connectorReviewFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Customer\Review\RatingFactory
     */
    private $ratingFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Review\CollectionFactory
     */
    private $reviewCollection;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $coreDate;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory
     */
    private $reviewResourceFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * Review constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Review\CollectionFactory $reviewCollection
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                         $coreDate
     * @param \Dotdigitalgroup\Email\Model\Customer\Review\RatingFactory          $ratingFactory
     * @param \Dotdigitalgroup\Email\Model\Customer\ReviewFactory                 $connectorFactory
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory                        $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                                  $data
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory            $reviewResourceFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Review\CollectionFactory $reviewCollection,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Dotdigitalgroup\Email\Model\Customer\Review\RatingFactory $ratingFactory,
        \Dotdigitalgroup\Email\Model\Customer\ReviewFactory $connectorFactory,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory $reviewResourceFactory,
        ScopeConfigInterface $scopeConfig,
        OrderFactory $orderFactory
    ) {
        $this->coreDate               = $coreDate;
        $this->reviewCollection       = $reviewCollection;
        $this->ratingFactory          = $ratingFactory;
        $this->connectorReviewFactory = $connectorFactory;
        $this->importerFactory        = $importerFactory;
        $this->helper                 = $data;
        $this->reviewResourceFactory  = $reviewResourceFactory;
        $this->scopeConfig = $scopeConfig;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Sync
     * - Create campaigns for review automations
     * - Sync reviews to Engagement Cloud
     *
     * @param \DateTime|null $from
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync(\DateTime $from = null)
    {
        $this->orderFactory->create()
            ->createReviewCampaigns();

        return $this->syncReviews();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function syncReviews()
    {
        $response = ['success' => true, 'message' => 'Done.'];

        $limit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );

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
                $this->_exportReviewsForWebsite($website, $limit);
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
                $this->countReviews += count($reviews);
            }
        }

        $message = '----------- Review sync ----------- : ' .
            gmdate('H:i:s', microtime(true) - $this->start) .
            ', synced = ' . $this->countReviews;

        if ($this->countReviews) {
            $this->helper->log($message);
        }

        $response['message'] = $message;

        return $response;
    }

    /**
     * Export reviews for website.
     *
     * @param \Magento\Store\Model\Website $website
     * @param string|int $limit
     * @return null
     */
    public function _exportReviewsForWebsite(\Magento\Store\Model\Website $website, $limit)
    {
        $emailReviews    = $this->_getReviewsToExport($website, $limit);
        $this->reviewIds = [];

        if ($emailReviews->getSize()) {
            $ids = $emailReviews->getColumnValues('review_id');
            $reviewResourceFactory = $this->reviewResourceFactory->create();
            $reviews = $reviewResourceFactory->getMageReviewsByIds($ids);

            foreach ($reviews as $mageReview) {
                try {
                    $product = $reviewResourceFactory
                        ->getProductByIdAndStore($mageReview->getEntityPkValue(), $mageReview->getStoreId());

                    $connectorReview = $this->connectorReviewFactory->create()
                        ->setReviewData($mageReview)
                        ->setProduct($product);

                    $votesCollection = $reviewResourceFactory
                        ->getVoteCollectionByReview($mageReview->getReviewId());

                    foreach ($votesCollection as $ratingItem) {
                        $rating = $this->ratingFactory->create()
                            ->setRating($ratingItem);
                        $connectorReview->createRating(
                            $ratingItem->getRatingCode(),
                            $rating
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
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Review\Collection
     */
    public function _getReviewsToExport(\Magento\Store\Model\Website $website, $limit = 100)
    {
        return $this->reviewCollection->create()
            ->getReviewsToExportByWebsite($website, $limit);
    }

    /**
     * Set imported in bulk query.
     *
     * @param array $ids
     *
     * @return null
     */
    public function _setImported($ids)
    {
        $nowDate = $this->coreDate->gmtDate();
        $this->reviewResourceFactory->create()
            ->setImported($ids, $nowDate);
    }
}
