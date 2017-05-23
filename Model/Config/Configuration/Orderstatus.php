<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Orderstatus implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Sales\Model\Config\Source\Order\Status
     */
    private $status;

    /**
     * Orderstatus constructor.
     *
     * @param \Magento\Sales\Model\Config\Source\Order\Status $status
     */
    public function __construct(
        \Magento\Sales\Model\Config\Source\Order\Status $status
    ) {
        $this->status = $status;
    }

    /**
     * Returns the order statuses for field order_statuses.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $statuses = $this->status->toOptionArray();

        if (! empty($statuses) && $statuses[0]['value'] == '') {
            array_shift($statuses);
        }

        $options[] = [
            'label' => __('---- Default Option ----'),
            'value' => '0',
        ];

        foreach ($statuses as $status) {
            $options[] = [
                'value' => $status['value'],
                'label' => $status['label'],
            ];
        }

        return $options;
    }
}
