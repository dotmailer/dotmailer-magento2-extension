<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Campaign extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $datetime;

    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_campaign', 'id');
    }

    /**
     * Campaign constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
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
     * @param $ids
     * @param $message
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setMessage($ids, $message)
    {
        try {
            $ids = implode(", ", $ids);
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                [
                    'message' => $message,
                    'send_status' => \Dotdigitalgroup\Email\Model\Campaign::FAILED,
                    'sent_at' =>  $this->datetime->gmtDate()
                ],
                "id in ($ids)"
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Set error message on given send id
     *
     * @param $sendId
     * @param $message
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setMessageWithSendId($sendId, $message)
    {
        try {
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
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Set sent
     *
     * @param $sendId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setSent($sendId)
    {
        try {
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
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Set processing
     *
     * @param $ids
     * @param $sendId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setProcessing($ids, $sendId)
    {
        try {
            $ids = implode(', ', $ids);
            $bind = [
                'send_status' => \Dotdigitalgroup\Email\Model\Campaign::PROCESSING,
                'send_id' => $sendId
            ];
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                $bind,
                "id in ($ids)"
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }
}
