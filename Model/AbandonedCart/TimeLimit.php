<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\SyncTimeService;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class TimeLimit
{
    /**
     * @var SyncTimeService
     */
    private $syncTimeService;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @param SyncTimeService $syncTimeService
     * @param TimezoneInterface $timezone
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        SyncTimeService $syncTimeService,
        TimezoneInterface $timezone,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->timezone = $timezone;
        $this->scopeConfig = $scopeConfig;
        $this->syncTimeService = $syncTimeService;
    }

    /**
     * Get an updated range according to the configured limit.
     *
     * @param int $storeId
     *
     * @return array|bool
     * @throws \Exception
     */
    public function getAbandonedCartTimeLimit($storeId)
    {
        $cartLimit = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_ABANDONED_CART_LIMIT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        //no limit is set skip
        if (!$cartLimit) {
            return false;
        }

        $toTime = $this->syncTimeService->getSyncFromTime() ?: $this->syncTimeService->getUTCNowTime();
        $scopedToTime = $this->timezone->scopeDate($storeId, $toTime->format('Y-m-d H:i:s'), true);
        $scopedFromTime = clone $scopedToTime;
        $scopedFromTime->sub(new \DateInterval(sprintf('PT%sH', $cartLimit)));

        return [
            'from' => $scopedFromTime->getTimestamp(),
            'to' => $scopedToTime->getTimestamp(),
            'date' => true,
        ];
    }
}
