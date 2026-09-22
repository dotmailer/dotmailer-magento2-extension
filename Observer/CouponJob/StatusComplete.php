<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Observer\CouponJob;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\Integration\IntegrationInsights;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Fires after a CouponJob row is committed to the database.
 * Reacts when the status column transitions to STATUS_COMPLETE and
 * publishes an integration-insights sync.
 */
class StatusComplete implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PublisherInterface $publisher
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->publisher = $publisher;
    }

    /**
     * Execute observer.
     *
     * Only acts on the transition to STATUS_COMPLETE, not on repeated saves.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!(bool) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_INTEGRATION_INSIGHTS_ENABLED)) {
            return;
        }

        /** @var \Dotdigitalgroup\Email\Model\CouponJob $couponJob */
        $couponJob = $observer->getEvent()->getData('coupon_job');

        $newStatus = $couponJob->getStatus();
        $oldStatus = $couponJob->getOrigData('status');

        // Only act on the transition *to* complete, not on repeated saves
        if ($newStatus !== CouponJobInterface::STATUS_COMPLETE || $oldStatus === CouponJobInterface::STATUS_COMPLETE) {
            return;
        }

        $this->publisher->publish(IntegrationInsights::TOPIC_SYNC_INTEGRATION, '');
    }
}
