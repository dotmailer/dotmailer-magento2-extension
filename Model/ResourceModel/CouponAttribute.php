<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class CouponAttribute extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_COUPON_TABLE, 'id');
    }
}
