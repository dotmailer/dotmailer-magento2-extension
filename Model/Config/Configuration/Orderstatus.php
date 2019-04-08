<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Orderstatus implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    private $orderConfig;

    /**
     * Orderstatus constructor.
     *
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     */
    public function __construct(
        \Magento\Sales\Model\Order\Config $orderConfig
    ) {
        $this->orderConfig = $orderConfig;
    }

    /**
     * Returns the order statuses for field order_statuses.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $statuses = $this->orderConfig->getStatuses();

        $options[] = [
            'label' => __('---- Default Option ----'),
            'value' => '0',
        ];

        foreach ($statuses as $code => $label) {
            $options[] = ['value' => $code, 'label' => $label];
        }

        return $options;
    }
}
