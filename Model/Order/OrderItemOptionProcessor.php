<?php

namespace Dotdigitalgroup\Email\Model\Order;

use Dotdigitalgroup\Email\Model\Connector\KeyValidator;
use Magento\Sales\Model\Order\Item;

class OrderItemOptionProcessor
{
    /**
     * @var KeyValidator
     */
    private $validator;

    /**
     * OrderItemOptionProcessor constructor.
     *
     * @param KeyValidator $validator
     */
    public function __construct(
        KeyValidator $validator
    ) {
        $this->validator = $validator;
    }

    /**
     * Process options for each order item.
     *
     * @param Item $orderItem
     * @return array
     */
    public function process(Item $orderItem): array
    {
        $orderItemOptions = $orderItem->getProductOptions();

        //if product doesn't have options
        if (!array_key_exists('options', $orderItemOptions)) {
            return [];
        }

        $orderItemOptions = $orderItemOptions['options'];

        //if product options isn't array
        if (!is_array($orderItemOptions)) {
            return [];
        }

        $options = [];

        foreach ($orderItemOptions as $orderItemOption) {
            if (array_key_exists('value', $orderItemOption) &&
                array_key_exists('label', $orderItemOption)
            ) {
                $label = $this->validator->cleanLabel(
                    $orderItemOption['label'],
                    '-',
                    '-',
                    $orderItemOption['option_id'] ?? null
                );
                if (empty($label)) {
                    continue;
                }
                $options[][$label] = $orderItemOption['value'];
            }
        }

        return $options;
    }
}
