<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Groups the stores of a website into cohorts that share one customer or guest list.
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
        return $this->buildCohorts(
            $website,
            function (int $storeId) {
                return $this->helper->getCustomerListByStoreId($storeId);
            }
        );
    }

    /**
     * Resolve guest list cohorts for a website.
     *
     * Same shape as resolve(), but stores are resolved against the guest list. Guests are placed by the
     * store they first made contact in (email_contact.store_id), so includeUnassigned covers guests
     * whose store is missing or outside the website.
     *
     * @param WebsiteInterface $website
     *
     * @return array[]
     * @throws LocalizedException
     */
    public function resolveGuests(WebsiteInterface $website): array
    {
        return $this->buildCohorts(
            $website,
            function (int $storeId) {
                return $this->helper->getGuestListByStoreId($storeId);
            }
        );
    }

    /**
     * Build cohorts using the given store-scoped list lookup.
     *
     * @param WebsiteInterface $website
     * @param callable $listLookup
     *
     * @return array[]
     * @throws LocalizedException
     */
    private function buildCohorts(WebsiteInterface $website, callable $listLookup): array
    {
        $websiteId = (int) $website->getId();
        $fallbackStoreId = $this->registrationStoreResolver->getFallbackStoreId($websiteId);
        $onlySubscribers = $this->helper->isOnlySubscribersForContactSync($websiteId);
        $cohorts = [];

        foreach ($this->registrationStoreResolver->getWebsiteStoreIds($websiteId) as $storeId) {
            $listId = (int) $listLookup($storeId);
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
