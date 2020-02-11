<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

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
     * Set a campaign as sent.
     * The sent_at date is set via the response data from Engagement Cloud.
     *
     * @param int $sendId
     * @param string $sendDate
     *
     * @return null
     */
    public function setSent($sendId, $sendDate)
    {
        $sendDateObject = new \DateTime($sendDate, new \DateTimeZone('UTC'));
        $sentAt = $sendDateObject->format('Y-m-d H:i:s');
        $bind = [
            'send_status' => \Dotdigitalgroup\Email\Model\Campaign::SENT,
            'sent_at' => $sentAt
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

    /**
     * @param array $ids
     *
     * @return null
     */
    public function expireCampaigns($ids)
    {
        $bind = [
            'send_status' => \Dotdigitalgroup\Email\Model\Campaign::SENT,
            'message' => 'Check sending status in Engagement Cloud',
            'updated_at' => $this->datetime->gmtDate()
        ];
        $this->getConnection()
            ->update(
                $this->getMainTable(),
                $bind,
                ["id in (?)" => $ids]
            );
    }
}
