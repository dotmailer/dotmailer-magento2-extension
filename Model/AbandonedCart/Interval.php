<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart;

use DateInterval;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\SyncTimeService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Interval
{
    private const STANDARD_ABANDONED_CART_INTERVAL = 'PT5M';
    public const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_1 = 'abandoned_carts/customers/send_after_1';
    public const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_2 = 'abandoned_carts/customers/send_after_2';
    public const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_3 = 'abandoned_carts/customers/send_after_3';
    public const XML_PATH_LOSTBASKET_GUEST_INTERVAL_1 = 'abandoned_carts/guests/send_after_1';
    public const XML_PATH_LOSTBASKET_GUEST_INTERVAL_2 = 'abandoned_carts/guests/send_after_2';
    public const XML_PATH_LOSTBASKET_GUEST_INTERVAL_3 = 'abandoned_carts/guests/send_after_3';

    /**
     * @var SyncTimeService
     */
    private $syncTimeService;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Interval constructor.
     *
     * @param SyncTimeService $syncTimeService
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        SyncTimeService $syncTimeService,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->syncTimeService = $syncTimeService;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Set time window for abandoned cart program enrolments
     *
     * @param int $storeId
     *
     * @return array
     * @throws \Exception
     */
    public function getAbandonedCartProgramEnrolmentWindow($storeId): array
    {
        $interval = $this->getIntervalForProgramEnrolment($storeId);
        return $this->getUpdatedAtWindow($interval);
    }

    /**
     * Get abandoned cart series window.
     *
     * @param DateInterval $interval
     *
     * @return array
     * @throws \Exception
     */
    public function getAbandonedCartSeriesWindow(DateInterval $interval): array
    {
        return $this->getUpdatedAtWindow($interval);
    }

    /**
     * Get window for AC customer series.
     *
     * @param int $storeId
     * @param int $num
     *
     * @return array
     * @throws \Exception
     */
    public function getAbandonedCartSeriesCustomerWindow($storeId, $num): array
    {
        $interval = $this->getIntervalForCustomerEmailSeries($storeId, $num);
        return $this->getUpdatedAtWindow($interval);
    }

    /**
     * Get window for AC customer series.
     *
     * @param int $storeId
     * @param int $num
     *
     * @return array
     * @throws \Exception
     */
    public function getAbandonedCartSeriesGuestWindow($storeId, $num): array
    {
        $interval = $this->getIntervalForGuestEmailSeries($storeId, $num);
        return $this->getUpdatedAtWindow($interval);
    }

    /**
     * Get the 'Send After' interval in minutes or hours.
     *
     * @param int $storeId
     * @param int $num
     *
     * @return DateInterval
     */
    public function getIntervalForCustomerEmailSeries($storeId, $num): DateInterval
    {
        $timeInterval = $this->getLostBasketCustomerInterval($num, $storeId);

        return $num == 1 ?
            new DateInterval(sprintf('PT%sM', $timeInterval)) :
            new DateInterval(sprintf('PT%sH', $timeInterval));
    }

    /**
     * Get the 'Send After' interval in minutes or hours.
     *
     * @param int $storeId
     * @param int $num
     *
     * @return DateInterval
     */
    public function getIntervalForGuestEmailSeries($storeId, $num): DateInterval
    {
        $timeInterval = $this->getLostBasketGuestInterval($num, $storeId);

        return $num == 1 ?
            new DateInterval(sprintf('PT%sM', $timeInterval)) :
            new DateInterval(sprintf('PT%sH', $timeInterval));
    }

    /**
     * Get updated at time window.
     *
     * @param DateInterval $interval
     *
     * @return array
     * @throws \Exception
     */
    private function getUpdatedAtWindow(DateInterval $interval): array
    {
        if ($fromTime = $this->syncTimeService->getSyncFromTime()) {
            $toTime = $this->syncTimeService->getUTCNowTime();
        } else {
            $toTime = $this->syncTimeService->getSyncToTime($interval);
            $fromTime = clone $toTime;
            $fromTime->sub(new DateInterval(self::STANDARD_ABANDONED_CART_INTERVAL));
        }

        return [
            'from' => $fromTime->format('Y-m-d H:i:s'),
            'to' => $toTime->format('Y-m-d H:i:s'),
            'date' => true,
        ];
    }

    /**
     * Get the 'Send After' interval in minutes or hours.
     *
     * @param int $storeId
     *
     * @return DateInterval
     */
    private function getIntervalForProgramEnrolment($storeId): DateInterval
    {
        $minutes = $this->scopeConfig->getValue(
            Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_INTERVAL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return new DateInterval(sprintf('PT%sM', $minutes));
    }

    /**
     * Get the 'Send After' value for this number in the series.
     *
     * @param int $num
     * @param int $storeId
     *
     * @return string
     */
    private function getLostBasketCustomerInterval($num, $storeId): string
    {
        return $this->scopeConfig->getValue(
            constant('self::XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_' . $num),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the 'Send After' value for this number in the series.
     *
     * @param int $num
     * @param int $storeId
     *
     * @return string
     */
    private function getLostBasketGuestInterval($num, $storeId): string
    {
        return $this->scopeConfig->getValue(
            constant('self::XML_PATH_LOSTBASKET_GUEST_INTERVAL_' . $num),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
