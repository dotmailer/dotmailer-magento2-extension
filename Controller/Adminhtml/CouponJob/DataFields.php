<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V2\ClientFactory;
use Dotdigitalgroup\Email\Model\Customer\CustomerDataFieldProviderFactory;
use Dotdigitalgroup\Email\Traits\FilterAndRankDotdigitalRecord;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;

class DataFields extends Action
{
    use FilterAndRankDotdigitalRecord;

    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::coupon_job';
    public const CACHE_PREFIX = 'DDG';
    public const CACHE_TTL = 60;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var ClientFactory
     */
    private $dotdigitalClient;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerDataFieldProviderFactory
     */
    private $customerDataFieldProviderFactory;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CacheInterface $cache
     * @param ClientFactory $dotdigitalClient
     * @param SerializerInterface $serializer
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param CustomerDataFieldProviderFactory $customerDataFieldProviderFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CacheInterface $cache,
        ClientFactory $dotdigitalClient,
        SerializerInterface $serializer,
        Logger $logger,
        StoreManagerInterface $storeManager,
        CustomerDataFieldProviderFactory $customerDataFieldProviderFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->cache = $cache;
        $this->dotdigitalClient = $dotdigitalClient;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->customerDataFieldProviderFactory = $customerDataFieldProviderFactory;
    }

    /**
     * Fetch string-type data fields for the coupon job data field typeahead.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->jsonFactory->create();
        $websiteId = (int) $this->getRequest()->getParam('website_id', 0);
        $query = strtolower($this->getRequest()->getParam('q', ''));

        try {
            $dataFields = $this->getRecords($websiteId);
            $scoredResults = $this->filterAndRankRecords($dataFields, $query);
            $result->setData($scoredResults);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $result->setData(['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Return all data fields for the given website, loading from cache when available.
     *
     * @param int $websiteId
     * @return array
     * @throws LocalizedException
     */
    private function getRecords(int $websiteId): array
    {
        $cacheKey = self::CACHE_PREFIX . "_{$websiteId}_" . self::class;
        $cached   = $this->cache->load($cacheKey) ?? null;

        if ($cached) {
            return $this->serializer->unserialize($cached);
        }

        $excludedFields = ['FIRSTNAME', 'FULLNAME', 'GENDER', 'LASTNAME', 'POSTCODE'];

        $website = $this->storeManager->getWebsite($websiteId);
        $customerDataFields = $this->customerDataFieldProviderFactory
            ->create(['data' => ['website' => $website]])
            ->getCustomerDataFields();
        $excludedFields = array_unique(array_merge($excludedFields, $customerDataFields));

        $stringDataFields = array_filter(
            $this->dotdigitalClient->create(['data' => ['websiteId' => $websiteId]])->dataFields->show()->getList(),
            fn ($entry) => $entry->getType() === "String" && !in_array($entry->getName(), $excludedFields, true)
        );

        $records = array_map(
            fn ($entry) => [ 'name' => $entry->getName(), 'type' => $entry->getType() ],
            $stringDataFields
        );

        $this->cache->save(
            $this->serializer->serialize($records),
            $cacheKey,
            [],
            self::CACHE_TTL
        );

        return $records;
    }
}
