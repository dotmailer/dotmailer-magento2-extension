<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Groups the stores of a website into cohorts that share one customer list.
 *
 * Stores inheriting the same website list form a single cohort, so import volume is unchanged.
 */
class ListCohortResolver
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var RegistrationStoreResolver
     */
    private $registrationStoreResolver;

    /**
     * @param Data $helper
     * @param RegistrationStoreResolver $registrationStoreResolver
     */
    public function __construct(
        Data $helper,
        RegistrationStoreResolver $registrationStoreResolver
    ) {
        $this->helper = $helper;
        $this->registrationStoreResolver = $registrationStoreResolver;
    }

    /**
     * Resolve customer list cohorts for a website.
     *
     * Each cohort is an array with the keys listId, onlySubscribers, storeIds and includeUnassigned.
     * onlySubscribers is the website-scoped allow-non-subscribers gate, identical for every cohort.
     * includeUnassigned is true for the cohort holding the website's fallback store: it also takes
     * customers whose registration store is missing or outside the website.
     * Stores with no customer list mapped are skipped.
     *
     * @param WebsiteInterface $website
     *
     * @return array[]
     * @throws LocalizedException
     */
    public function resolve(WebsiteInterface $website): array
    {
        $websiteId = (int) $website->getId();
        $fallbackStoreId = $this->registrationStoreResolver->getFallbackStoreId($websiteId);
        $onlySubscribers = $this->helper->isOnlySubscribersForContactSync($websiteId);
        $cohorts = [];

        foreach ($this->registrationStoreResolver->getWebsiteStoreIds($websiteId) as $storeId) {
            $listId = (int) $this->helper->getCustomerListByStoreId($storeId);
            if (!$listId) {
                continue;
            }

            if (!isset($cohorts[$listId])) {
                $cohorts[$listId] = [
                    'listId' => $listId,
                    'onlySubscribers' => $onlySubscribers,
                    'storeIds' => [],
                    'includeUnassigned' => false
                ];
            }

            $cohorts[$listId]['storeIds'][] = $storeId;
            if ($storeId === $fallbackStoreId) {
                $cohorts[$listId]['includeUnassigned'] = true;
            }
        }

        return array_values($cohorts);
    }
}
