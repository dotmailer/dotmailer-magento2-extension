<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

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
     * @return null
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
}
