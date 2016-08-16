<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Campaign extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
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
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                [
                    'message' => $message,
                    'is_sent' => 1,
                    'sent_at' => time()
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
            $bind = [
                'is_sent' => 1,
                'sent_at' => time()
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
