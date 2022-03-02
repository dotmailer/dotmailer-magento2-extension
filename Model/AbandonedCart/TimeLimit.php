<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\SetsSyncFromTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
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
     * @param TimezoneInterface $timezone
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        TimezoneInterface $timezone,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->timezone = $timezone;
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

        $interval = new \DateInterval(sprintf('PT%sH', $cartLimit));
        $fromTime->sub($interval);

        return [
            'from' => $fromTime->getTimestamp(),
            'to' => $toTime->getTimestamp(),
            'date' => true,
        ];
    }
}
