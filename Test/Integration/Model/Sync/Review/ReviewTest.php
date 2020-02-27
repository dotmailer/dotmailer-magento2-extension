<?php

namespace Dotdigitalgroup\Email\Test\Integration\Model\Sync\Review;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\Serialize\SerializerInterface;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\Sync\Review;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;

class ReviewTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    const BASE_WEBSITE_CODE = 'base';
    const SECOND_WEBSITE_CODE = 'test';

    const BASE_WEBSITE_ID = 1;
    const SECOND_WEBSITE_ID = 2;

    /**
     * @var Review
     */
    private $reviewSync;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Importer
     */
    private $importerCollection;

    /**
     * @var Review
     */
    private $emailReviewCollection;

    /**
     * @var \Magento\Review\Model\Review
     */
    private $mageReviewCollection;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Review\Model\Review
     */
    private $mageReview;

    /**
     * @var Customer
     */
    private $reviewCustomer;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    private $reviewStore;
    /**
     * @var \Magento\Store\Api\Data\WebsiteInterface
     */
    private $reviewWebsite;

    /**
     * @var Product
     */
    private $reviewProduct;

    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->importerCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection::class
        );

        $this->emailReviewCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Review\Collection::class
        );

        $this->mageReviewCollection = $this->objectManager->create(
            \Magento\Review\Model\ResourceModel\Review\Collection::class
        );

        $this->serializer = $this->objectManager->create(
            SerializerInterface::class
        );

        $this->customerFactory = $this->objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();

        $this->storeManager = $this->objectManager->create(StoreManagerInterface::class);

        $this->reviewSync = $this->objectManager->create(Review::class);
    }

    private function runSyncs($configurations)
    {
        foreach ($configurations as $websiteCode => $reviewSyncEnabled) {
            $this->setApiConfigFlags([
                Config::XML_PATH_CONNECTOR_SYNC_REVIEW_ENABLED => $reviewSyncEnabled,
            ], $websiteCode, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES);
        }

        $this->reviewSync->sync();
    }

    private function validateImportedData($importedData, $importedDataJson)
    {
        $this->assertEquals($importedData->getImportType(), \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_REVIEWS);
        $this->assertEquals($importedData->getImportMode(), \Dotdigitalgroup\Email\Model\Importer::MODE_BULK);
        $this->assertEquals($importedData->getImportStatus(), Importer::NOT_IMPORTED);

        $this->assertEquals($importedDataJson['customerId'], $this->mageReview->getCustomerId());
        $this->assertEquals($importedDataJson['email'], $this->reviewCustomer->getEmail());
        $this->assertEquals($importedDataJson['productName'], $this->reviewProduct->getName());
        $this->assertEquals($importedDataJson['productSku'], $this->reviewProduct->getSku());
        $this->assertEquals($importedDataJson['websiteName'], $this->reviewWebsite->getName());
        $this->assertEquals($importedDataJson['storeName'], $this->reviewStore->getName());
    }

    /**
     * @magentoDataFixture loadFixture
     * @magentoAppIsolation enabled
     */
    public function testThatInTwoWebsiteInstanceWeAreSyncingTheCorrectDataForBaseWebsite()
    {
        /**
         * $websiteCode = $key
         * $reviewSyncEnabled = $value
         */
        $configurations = [
            self::BASE_WEBSITE_CODE => 1,
            self::SECOND_WEBSITE_CODE => 1
        ];

        $this->runSyncs($configurations);

        $importedData = $this->importerCollection
            ->addFieldToFilter('website_id', self::BASE_WEBSITE_ID)
            ->getFirstItem();

        $importedDataJson = $importedData->getImportData();
        $importedDataJson = $this->serializer->unserialize($importedDataJson);
        $importedDataJson = reset($importedDataJson);

        $emailReview = $this->emailReviewCollection
            ->addFieldToFilter('id', (string)$importedDataJson['id'])
            ->getFirstItem();

        $this->mageReview = $this->mageReviewCollection
            ->addFieldToFilter('detail_id', $emailReview->getId())
            ->getFirstItem();

        $this->reviewCustomer = $this->customerFactory->load($this->mageReview->getCustomerId());

        $productCollection = $this->mageReview->getProductCollection();
        $this->reviewProduct = $productCollection
            ->addFieldToFilter('entity_id', '1')
            ->getFirstItem();

        $this->reviewStore = $this->storeManager->getStore($this->reviewProduct->getStoreId());
        $this->reviewWebsite = $this->storeManager->getWebsite($this->reviewStore->getWebsiteId());

        $this->validateImportedData($importedData, $importedDataJson);
    }

    public static function loadFixture()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $filesystem = $objectManager->create(Filesystem::class);
        $directoryList = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $reviewRating1 = 'dev/tests/integration/testsuite/Magento/Review/_files/customer_review_with_rating.php';
        $reviewRating2 = '/../../../_files/customer_review_with_rating_second_website.php';

        require $directoryList->getAbsolutePath() . $reviewRating1;
        require __DIR__ . $reviewRating2;
    }

    /**
     * @magentoDataFixture loadFixture
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testThatInTwoWebsiteInstanceWeAreSyncingTheCorrectDataForSecondWebsite()
    {
        /**
         * $websiteCode = $key
         * $reviewSyncEnabled = $value
         */
        $configurations = [
            self::BASE_WEBSITE_CODE => 1,
            self::SECOND_WEBSITE_CODE => 1
        ];

        $this->runSyncs($configurations);

        $importedData = $this->importerCollection
            ->addFieldToFilter('website_id', 3)
            ->getFirstItem();

        $importedDataJson = $importedData->getImportData();
        $importedDataJson = $this->serializer->unserialize($importedDataJson);
        $importedDataJson = reset($importedDataJson);

        $emailReview = $this->emailReviewCollection
            ->addFieldToFilter('id', (string)$importedDataJson['id'])
            ->getFirstItem();

        $this->mageReview = $this->mageReviewCollection
            ->addFieldToFilter('detail_id', $emailReview->getId())
            ->getFirstItem();

        $this->reviewCustomer = $this->customerFactory->load($this->mageReview->getCustomerId());

        $productCollection = $this->mageReview->getProductCollection();
        $this->reviewProduct = $productCollection
            ->addFieldToFilter('sku', 'unique-simple-azaza')
            ->getFirstItem();

        $this->reviewStore = $this->storeManager->getStore($this->reviewProduct->getStoreId());
        $this->reviewWebsite = $this->storeManager->getWebsite($this->reviewStore->getWebsiteId());

        $this->validateImportedData($importedData, $importedDataJson);
    }

    /**
     * @magentoDataFixture loadFixture
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testThatGivenASingleReviewWhenSyncedTheEmailReviewHasTheCorrectCustomerId()
    {
        /**
         * $websiteCode = $key
         * $reviewSyncEnabled = $value
         */
        $configurations = [
            self::BASE_WEBSITE_CODE => 1,
            self::SECOND_WEBSITE_CODE => 0
        ];

        $this->runSyncs($configurations);

        //Get Customer Id from Review that created from dataFixtures
        $mageReviewCustomerId = $this->mageReviewCollection->getFirstItem()->getCustomerId();

        //Get Customer Id from record that created in email_review table
        $emailReviewCustomerId = $this->emailReviewCollection->getFirstItem()->getCustomerId();

        $this->assertEquals($mageReviewCustomerId, $emailReviewCustomerId);
    }

    /**
     * @magentoDataFixture loadFixture
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testThatGivenASingleReviewWhenSyncedTheEmailReviewIsMarkedAsImported()
    {
        /**
         * $websiteCode = $key
         * $reviewSyncEnabled = $value
         */
        $configurations = [
            self::BASE_WEBSITE_CODE => 1,
            self::SECOND_WEBSITE_CODE => 0
        ];

        $this->runSyncs($configurations);

        $emailReview = $this->emailReviewCollection
            ->addFieldToFilter('review_id', $this->mageReviewCollection->getFirstItem()->getReviewId())
            ->getFirstItem();

        $this->assertEquals($emailReview->getReviewImported(), 1);
    }
}
