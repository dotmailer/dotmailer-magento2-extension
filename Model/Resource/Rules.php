<?php

namespace Dotdigitalgroup\Email\Model\Resource;

class Rules extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_rules', 'id');
    }
}
