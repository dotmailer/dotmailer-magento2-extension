<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class Abandoned extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * Initialize resource.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_ABANDONED_CART_TABLE, 'id');
    }

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->helper = $data;
        parent::__construct($context);
    }

    /**
     * Update.
     *
     * @param array $ids
     * @param string $date
     * @param bool $contactStatus
     */
    public function update($ids, $date, $contactStatus)
    {
        if (empty($ids)) {
            return;
        }

        $bind = ['updated_at' => $date, 'status' => $contactStatus];

        $where = ['id IN(?)' => $ids];
        $this->getConnection()->update(
            $this->getTable(Schema::EMAIL_ABANDONED_CART_TABLE),
            $bind,
            $where
        );
    }

    /**
     * Update by quote ids.
     *
     * @param array $ids
     * @param string $date
     * @param string $contactStatus
     * @param bool $isActive
     */
    public function updateByQuoteIds($ids, $date, $contactStatus, $isActive)
    {
        $bind = [
            'updated_at' => $date,
            'status'     => $contactStatus,
            'is_active'  => $isActive
        ];

        $where = ['quote_id IN(?)' => $ids];
        $this->getConnection()->update(
            $this->getTable(Schema::EMAIL_ABANDONED_CART_TABLE),
            $bind,
            $where
        );
    }

    /**
     * Update Email for pending abandoned carts.
     *
     * @param string $emailBefore
     * @param string $newEmail
     * @return void
     */
    public function updateEmailForPendingAbandonedCarts($emailBefore, $newEmail)
    {
        $bind = ['email' => $newEmail];
        $where = ['email = ?' => $emailBefore, 'status = ?' => StatusInterface::PENDING_OPT_IN];

        $this->getConnection()->update(
            $this->getTable(Schema::EMAIL_ABANDONED_CART_TABLE),
            $bind,
            $where
        );
    }
}
