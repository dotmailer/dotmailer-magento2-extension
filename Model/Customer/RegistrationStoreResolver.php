<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Customer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolves the store a customer registered in (customer_entity.store_id).
 *
 * email_contact.store_id is the original/first-contact marker and is not used here, as it can
 * belong to a guest order, a subscription or another website's contact row.
 */
class RegistrationStoreResolver
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    /**
     * Resolve the registration store of a customer within a website.
     *
     * @param int $customerId
     * @param int $websiteId
     *
     * @return int
     * @throws LocalizedException
     */
    public function resolve(int $customerId, int $websiteId): int
    {
        $stores = $this->resolveMany([$customerId], $websiteId);

        return $stores[$customerId];
    }

    /**
     * Resolve the registration stores of customers within a website.
     *
     * Where the registration store is missing, or belongs to another website
     * (shared customer accounts), the website's default store is used.
     *
     * @param int[] $customerIds
     * @param int $websiteId
     *
     * @return int[] Store IDs keyed by customer ID.
     * @throws LocalizedException
     */
    public function resolveMany(array $customerIds, int $websiteId): array
    {
        $fallbackStoreId = $this->getFallbackStoreId($websiteId);
        $resolved = array_fill_keys(array_map('intval', $customerIds), $fallbackStoreId);

        if (!$resolved) {
            return $resolved;
        }

        $websiteStoreIds = $this->getWebsiteStoreIds($websiteId);
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                $this->resourceConnection->getTableName('customer_entity'),
                ['entity_id', 'store_id']
            )
            ->where('entity_id IN (?)', array_keys($resolved));

        foreach ($connection->fetchPairs($select) as $customerId => $storeId) {
            if ($storeId !== null && in_array((int) $storeId, $websiteStoreIds, true)) {
                $resolved[(int) $customerId] = (int) $storeId;
            }
        }

        return $resolved;
    }

    /**
     * Get the store used when a customer has no registration store in the website.
     *
     * @param int $websiteId
     *
     * @return int
     * @throws LocalizedException
     */
    public function getFallbackStoreId(int $websiteId): int
    {
        try {
            /** @var \Magento\Store\Model\Website $website */
            $website = $this->storeManager->getWebsite($websiteId);
            $defaultStore = $website->getDefaultStore();
        } catch (NoSuchEntityException $e) {
            return 0;
        }

        return $defaultStore ? (int) $defaultStore->getId() : 0;
    }

    /**
     * Get the IDs of stores in a website.
     *
     * @param int $websiteId
     *
     * @return int[]
     * @throws LocalizedException
     */
    public function getWebsiteStoreIds(int $websiteId): array
    {
        try {
            /** @var \Magento\Store\Model\Website $website */
            $website = $this->storeManager->getWebsite($websiteId);
            return array_map('intval', $website->getStoreIds());
        } catch (NoSuchEntityException $e) {
            return [];
        }
    }
}
