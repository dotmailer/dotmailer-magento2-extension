<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Queue;

use Magento\MysqlMq\Model\QueueManagement;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * To option array.
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => QueueManagement::MESSAGE_STATUS_NEW,
                'label' => 'Pending',
            ],
            [
                'value' => QueueManagement::MESSAGE_STATUS_IN_PROGRESS,
                'label' => 'Processing',
            ],
            [
                'value' => QueueManagement::MESSAGE_STATUS_COMPLETE,
                'label' => 'Complete',
            ],
            [
                'value' => QueueManagement::MESSAGE_STATUS_RETRY_REQUIRED,
                'label' => 'Retry required',
            ],
            [
                'value' => QueueManagement::MESSAGE_STATUS_ERROR,
                'label' => 'Error',
            ],
            [
                'value' => QueueManagement::MESSAGE_STATUS_TO_BE_DELETED,
                'label' => 'To be deleted',
            ],
        ];
    }
}
