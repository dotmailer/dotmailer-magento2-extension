<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Magento\Framework\Exception\LocalizedException;

/**
 * Resolves the store-scoped subscriber list for a single subscriber.
 *
 * The subscriber store is the store of the newsletter subscriber record, which ChangeContactSubscription
 * mirrors to email_contact.store_id. It is never the live session, customer registration or guest order store.
 */
class SubscriberListResolver
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var RegistrationStoreResolver
     */
    private $storeResolver;

    /**
     * @param Data $helper
     * @param RegistrationStoreResolver $storeResolver
     */
    public function __construct(
        Data $helper,
        RegistrationStoreResolver $storeResolver
    ) {
        $this->helper = $helper;
        $this->storeResolver = $storeResolver;
    }

    /**
     * Resolve the subscriber store within a website.
     *
     * Uses the explicit store when given (queued message, subscriber record), otherwise the contact's store.
     *
     * @param int $websiteId
     * @param int|null $storeId
     * @param int|null $contactStoreId
     *
     * @return int
     * @throws LocalizedException
     */
    public function resolveStoreId(int $websiteId, ?int $storeId, ?int $contactStoreId = null): int
    {
        $resolved = $storeId ?: (int) $contactStoreId;

        if (!$resolved) {
            throw new LocalizedException(
                __('Subscriber store context is unavailable for website %1.', $websiteId)
            );
        }

        if (!in_array($resolved, $this->storeResolver->getWebsiteStoreIds($websiteId), true)) {
            throw new LocalizedException(
                __('Subscriber store %1 does not belong to website %2.', $resolved, $websiteId)
            );
        }

        return $resolved;
    }

    /**
     * Get the subscriber list for a store, inheriting from the website when not overridden.
     *
     * @param int $websiteId
     * @param int $storeId
     *
     * @return int
     */
    public function getListId(int $websiteId, int $storeId): int
    {
        return (int) $this->helper->getSubscriberListByStoreId($storeId);
    }
}
