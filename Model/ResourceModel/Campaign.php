<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Campaign extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_dateTime;

    /**
     * Campaign constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime $dateTime
    ) {
        $this->_dateTime = $dateTime;
        parent::__construct($context);
    }

    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_campaign', 'id');
    }

    /**
     * Set error message
     *
     * @param $campaignId
     * @param $message
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setMessage($campaignId, $message)
    {
        try {
            $now = $this->_dateTime->formatDate(true);
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                [
                    'message' => $message,
                    'is_sent' => 1,
                    'sent_at' => $now
                ],
                ['campaign_id = ?' => $campaignId]
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Set sent
     *
     * @param $campaignId
     * @param bool $sendId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setSent($campaignId, $sendId = false)
    {
        try {
            $now = $this->_dateTime->formatDate(true);
            $bind = [
                'is_sent' => 1,
                'sent_at' => $now
            ];
            if ($sendId) {
                $bind['send_id'] = $sendId;
            }
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                $bind,
                ['campaign_id = ?' => $campaignId]
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }
}
