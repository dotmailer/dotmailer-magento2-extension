<?php

namespace Dotdigitalgroup\Email\Model\Customer\DataField;

use Magento\Framework\Stdlib\DateTime\TimezoneInterfaceFactory;

class Date
{
    /**
     * @var TimezoneInterfaceFactory
     */
    private $localeDateFactory;

    public function __construct(
        TimezoneInterfaceFactory $localeDateFactory
    ) {
        $this->localeDateFactory = $localeDateFactory;
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
            return $adjustedDate->format(\DateTime::ATOM);
        }

        // For locales west of GMT i.e. -01:00 and below, adjust date by adding the current timezone offset
        $offset = new \DateInterval(sprintf('PT%sS', abs($timezoneOffset)));

        return $adjustedDate->add($offset)->format(\DateTime::ATOM);
    }
}
