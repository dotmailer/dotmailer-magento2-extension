<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\Schema;

class Campaign extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $datetime;

    /**
     * Initialize resource.
     * @return null
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_CAMPAIGN_TABLE, 'id');
    }

    /**
     * Campaign constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context       $context,
        \Magento\Framework\Stdlib\DateTime\DateTime             $dateTime,
        $connectionName = null
    ) {
        $this->datetime = $dateTime;
        parent::__construct(
            $context,
            $connectionName
        );
    }

    /**
     * Set error message
     *
     * @param array $ids
     * @param string $message
     *
     * @return null
     */
    public function setMessage($ids, $message)
    {
        $conn = $this->getConnection();
        $conn->update(
            $this->getMainTable(),
            [
                'message' => $message,
                'send_status' => \Dotdigitalgroup\Email\Model\Campaign::FAILED,
                'sent_at' =>  $this->datetime->gmtDate()
            ],
            ["id in (?)" => $ids]
        );
    }

    /**
     * @param int $sendId
     * @param string $message
     *
     * @return null
     */
    public function setMessageWithSendId($sendId, $message)
    {
        $conn = $this->getConnection();
        $conn->update(
            $this->getMainTable(),
            [
                'message' => $message,
                'send_status' => \Dotdigitalgroup\Email\Model\Campaign::FAILED,
                'sent_at' => $this->datetime->gmtDate()
            ],
            ['send_id = ?' => $sendId]
        );
    }

    /**
     * Set sent.
     *
     * @param int $sendId
     *
     * @return null
     */
    public function setSent($sendId)
    {
        $bind = [
            'send_status' => \Dotdigitalgroup\Email\Model\Campaign::SENT,
            'sent_at' => $this->datetime->gmtDate()
        ];
        $conn = $this->getConnection();
        $conn->update(
            $this->getMainTable(),
            $bind,
            ['send_id = ?' => $sendId]
        );
    }

    /**
     * Set processing
     *
     * @param array $ids
     * @param int $sendId
     *
     * @return null
     */
    public function setProcessing($ids, $sendId)
    {
        $bind = [
            'send_status' => \Dotdigitalgroup\Email\Model\Campaign::PROCESSING,
            'send_id' => $sendId
        ];
        $conn = $this->getConnection();
        $conn->update(
            $this->getMainTable(),
            $bind,
            ["id in (?)" => $ids]
        );
    }

    /**
     * Save item
     *
     * @param \Dotdigitalgroup\Email\Model\Campaign $item
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    public function saveItem($item)
    {
        return parent::save($item);
    }
}
