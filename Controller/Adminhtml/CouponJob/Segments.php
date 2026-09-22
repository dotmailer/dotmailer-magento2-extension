<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V2\ClientFactory;
use Dotdigitalgroup\Email\Traits\FilterAndRankDotdigitalRecord;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

class Segments extends Action
{
    use FilterAndRankDotdigitalRecord;

    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::coupon_job';
    public const CACHE_PREFIX   = 'DDG';
    public const CACHE_TTL      = 60;

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
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CacheInterface $cache
     * @param ClientFactory $dotdigitalClient
     * @param SerializerInterface $serializer
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CacheInterface $cache,
        ClientFactory $dotdigitalClient,
        SerializerInterface $serializer,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory      = $jsonFactory;
        $this->cache            = $cache;
        $this->dotdigitalClient = $dotdigitalClient;
        $this->serializer       = $serializer;
        $this->logger           = $logger;
    }

    /**
     * Fetch segments for the coupon job audience select.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result    = $this->jsonFactory->create();
        $websiteId = (int) $this->getRequest()->getParam('website_id', 0);
        $query     = strtolower((string) $this->getRequest()->getParam('q', ''));

        try {
            $segments      = $this->getRecords($websiteId);
            $scoredResults = $this->filterAndRankRecords($segments, $query);
            $result->setData($scoredResults);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $result->setData(['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Return all segments for the given website, loading from cache when available.
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

        $segments = $this->dotdigitalClient->create(['data' => ['websiteId' => $websiteId]])
            ->segments
            ->all()
            ->getList();

        $records = array_map(
            fn ($segment) => [
                'id' => (string) $segment->getId(),
                'name' => $segment->getName(),
                'contacts' => $segment->getContacts()
            ],
            $segments
        );

        $this->cache->save(
            $this->serializer->serialize($records),
            $cacheKey,
            [],
            self::CACHE_TTL
        );

        return $records;
    }

    /**
     * Rank by segment name.
     *
     * @param array $record
     * @return string
     */
    protected function getRankableValue(array $record): string
    {
        return strtolower($record['name']);
    }
}
