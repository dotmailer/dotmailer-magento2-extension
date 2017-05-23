<?php

namespace Dotdigitalgroup\Email\Model\Automation\Status;

/**
 * Class Options
 * @package Dotdigitalgroup\Email\Model\Automation\Status
 */
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
                'value' => 'suppressed'
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
        ];

        return $options;
    }
}
