<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class FailedAuth extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init('email_failed_auth', 'id');
    }
}
