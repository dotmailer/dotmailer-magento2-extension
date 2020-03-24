<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\SetsSyncFromTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Dotdigitalgroup\Email\Model\DateIntervalFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class TimeLimit
{
    use SetsSyncFromTime;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * @param TimezoneInterface $timezone
     * @param DateIntervalFactory $dateIntervalFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        TimezoneInterface $timezone,
        DateIntervalFactory $dateIntervalFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->timezone = $timezone;
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $storeId
     * @return array|bool
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

        $fromTime = $this->timezone->scopeDate($storeId, $this->getSyncFromTime()->format('Y-m-d H:i:s'), true);
        $toTime = clone $fromTime;
        $interval = $this->dateIntervalFactory->create(
            ['interval_spec' => sprintf('PT%sH', $cartLimit)]
        );
        $fromTime->sub($interval);

        return [
            'from' => $fromTime->getTimestamp(),
            'to' => $toTime->getTimestamp(),
            'date' => true,
        ];
    }
}
