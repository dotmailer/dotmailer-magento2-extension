<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

use Dotdigitalgroup\Email\Model\Cron\Cleaner;

class CleanerAllowedTables implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
     * Returns the order statuses for field order_statuses.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $tables = Cleaner::TABLES;
        $options = [];

        foreach ($tables as $table) {
            $options[] = ['value' => $table, 'label' => __($table)];
        }

        return $options;
    }
}
