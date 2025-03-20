<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\ResourceModel\SubscriberFactory as SubscriberResourceFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

/**
 * These methods were only introduced to the core Magento Newsletter module resource model
 * in Magento 2.4.0-p1 so are backported here to support 2.3.x.
 */
class BackportedSubscriberLoader
{
    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var SubscriberResourceFactory
     */
    private $subscriberResourceFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param SubscriberFactory $subscriberFactory
     * @param SubscriberResourceFactory $subscriberResourceFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        SubscriberResourceFactory $subscriberResourceFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->subscriberResourceFactory = $subscriberResourceFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Load by subscriber email.
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return Subscriber
     * @throws LocalizedException
     */
    public function loadBySubscriberEmail(string $email, int $websiteId): Subscriber
    {
        $subscriberResource = $this->subscriberResourceFactory->create();
        /** @var Website $website */
        $website = $this->storeManager->getWebsite($websiteId);
        $storeIds = $website->getStoreIds();
        $select = $subscriberResource->getConnection()->select()
            ->from($subscriberResource->getMainTable())
            ->where('subscriber_email = ?', $email)
            ->where('store_id IN (?)', $storeIds)
            ->limit(1);

        $data = $subscriberResource->getConnection()->fetchRow($select) ?: [];

        return $this->subscriberFactory->create()
            ->addData($data)
            ->setOrigData();
    }

    /**
     * Load by customer.
     *
     * @param int $customerId
     * @param int $websiteId
     *
     * @return Subscriber
     * @throws LocalizedException
     */
    public function loadByCustomer(int $customerId, int $websiteId)
    {
        $subscriberResource = $this->subscriberResourceFactory->create();
        /** @var Website $website */
        $website = $this->storeManager->getWebsite($websiteId);
        $storeIds = $website->getStoreIds();
        $select = $subscriberResource->getConnection()->select()
            ->from($subscriberResource->getMainTable())
            ->where('customer_id = ?', $customerId)
            ->where('store_id IN (?)', $storeIds)
            ->limit(1);

        $data = $subscriberResource->getConnection()->fetchRow($select) ?: [];

        return $this->subscriberFactory->create()
            ->addData($data)
            ->setOrigData();
    }
}
