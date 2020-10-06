<?php

namespace Dotdigitalgroup\Email\Model\Customer\DataField;

use Magento\Framework\Stdlib\DateTime\TimezoneInterfaceFactory;
use Dotdigitalgroup\Email\Model\DateIntervalFactory;

class Date
{
    /**
     * @var TimezoneInterfaceFactory
     */
    private $localeDateFactory;

    /**
     * @var DateIntervalFactory
     */
    private $dateIntervalFactory;

    public function __construct(
        TimezoneInterfaceFactory $localeDateFactory,
        DateIntervalFactory $dateIntervalFactory
    ) {
        $this->localeDateFactory = $localeDateFactory;
        $this->dateIntervalFactory = $dateIntervalFactory;
    }

    /**
     * Adjust date strings to the correctly-scoped timezone.
     * Used to prepare customer DOB and date-type customer preferences.
     * Returns ISO_8601 date string, because input formats can differ.
     *
     * @param $storeId
     * @param string $date
     * @return string
     */
    public function getScopeAdjustedDate($storeId, string $date)
    {
        $scopedDate = $this->localeDateFactory->create()
            ->scopeDate(
                $storeId,
                strtotime($date),
                true
            );

        $timezoneOffset = $scopedDate->getOffset();

        $adjustedDate = $this->localeDateFactory->create()
            ->date(
                strtotime($date),
                null,
                false
            );

        // For locales east of GMT i.e. +01:00 and up, return the date, formatted
        if ($timezoneOffset > 0) {
            return $adjustedDate->format(\Zend_Date::ISO_8601);
        }

        // For locales west of GMT i.e. -01:00 and below, adjust date by adding the current timezone offset
        $offset = $this->dateIntervalFactory->create(
            ['interval_spec' => 'PT' . abs($timezoneOffset) . 'S']
        );

        return $adjustedDate->add($offset)->format(\Zend_Date::ISO_8601);
    }
}
