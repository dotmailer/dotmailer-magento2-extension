<?php

namespace Dotdigitalgroup\Email\Model\Automation\Status;

use Dotdigitalgroup\Email\Model\Sync\Automation;

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
                'label' => Automation::CONTACT_STATUS_PENDING,
                'value' => Automation::CONTACT_STATUS_PENDING
            ],
            [
                'label' => Automation::CONTACT_STATUS_CONFIRMED,
                'value' => Automation::CONTACT_STATUS_CONFIRMED
            ],
            [
                'label' => Automation::CONTACT_STATUS_EXPIRED,
                'value' => Automation::CONTACT_STATUS_EXPIRED
            ],
            [
                'label' => Automation::AUTOMATION_STATUS_CANCELLED,
                'value' => Automation::AUTOMATION_STATUS_CANCELLED
            ],
        ];

        return $options;
    }
}
