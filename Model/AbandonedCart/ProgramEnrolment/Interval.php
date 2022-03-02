<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment;

class Interval
{
    /**
     * @var \Dotdigitalgroup\Email\Model\DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * Interval constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\DateTimeFactory $dateTimeFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\DateTimeFactory $dateTimeFactory,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->dateTimeFactory = $dateTimeFactory;
        $this->helper = $data;
    }

    /**
     * Set time window for abandoned cart program enrolments
     *
     * @param int $storeId
     * @param \DateTime|null $syncFromTime
     * @return array
     * @throws \Exception
     */
    public function getAbandonedCartProgramEnrolmentWindow($storeId, \DateTime $syncFromTime = null)
    {
        $fromTime = $syncFromTime ?: $this->dateTimeFactory->create(
            [
                'time' => 'now',
                'timezone' => new \DateTimezone('UTC')
            ]
        );

        $minutes = (int) $this->helper->getScopeConfig()->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_INTERVAL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $interval = new \DateInterval(sprintf('PT%sM', $minutes));
        $fromTime->sub($interval);
        $toTime = clone $fromTime;
        $fromTime->sub(new \DateInterval('PT5M'));

        return [
            'from' => $fromTime->format('Y-m-d H:i:s'),
            'to' => $toTime->format('Y-m-d H:i:s'),
            'date' => true,
        ];
    }
}
