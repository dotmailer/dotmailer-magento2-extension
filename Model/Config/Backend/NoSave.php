<?php

namespace Dotdigitalgroup\Email\Model\Config\Backend;

/**
 * Class NoSave
 * Prevents data from being saved to core_config_data.
 * Useful for fields where data is being sent via AJAX direct to EC e.g. Create New Data Field, Create New Address Book
 */
class NoSave extends \Magento\Framework\App\Config\Value
{
    /**
     * @return void
     */
    public function beforeSave()
    {
        $this->_dataSaveAllowed = false;
    }
}
