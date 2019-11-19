<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Carts;

class IntervalProgramEnrolment implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Abandoned cart program enrolment options [minutes].
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '15', 'label' => __('15 Minutes')],
            ['value' => '30', 'label' => __('30 Minutes')],
            ['value' => '60', 'label' => __('1 Hour')],
            ['value' => '360', 'label' => __('6 Hours')],
            ['value' => '720', 'label' => __('12 Hours')],
        ];
    }
}
