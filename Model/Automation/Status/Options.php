<?php

namespace Dotdigitalgroup\Email\Model\Automation\Status;

use Dotdigitalgroup\Email\Model\StatusInterface;

class Options implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @return array|null
     */
    public function toOptionArray()
    {
        $options = [
            [
                'label' => 'Pending',
                'value' => 'pending'
            ],
            [
                'label' => 'Suppressed',
                'value' => 'Suppressed'
            ],[
                'label' => 'Active',
                'value' => 'Active'
            ],[
                'label' => 'Draft',
                'value' => 'Draft'
            ],[
                'label' => 'Deactivated',
                'value' => 'Deactivated'
            ],[
                'label' => 'ReadOnly',
                'value' => 'ReadOnly'
            ],[
                'label' => 'NotAvailableInThisVersion',
                'value' => 'NotAvailableInThisVersion'
            ],
            [
                'label' => 'Failed',
                'value' => 'Failed'
            ],
            [
                'label' => StatusInterface::PENDING_OPT_IN,
                'value' => StatusInterface::PENDING_OPT_IN
            ],
            [
                'label' => StatusInterface::CONFIRMED,
                'value' => StatusInterface::CONFIRMED
            ],
            [
                'label' => StatusInterface::EXPIRED,
                'value' => StatusInterface::EXPIRED
            ],
            [
                'label' => StatusInterface::CANCELLED,
                'value' => StatusInterface::CANCELLED
            ],
        ];

        return $options;
    }
}
