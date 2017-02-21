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
     * @param $ids
     * @param $message
     * @param $sendId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setMessage($ids, $message, $sendId = false)
    {
        try {
            $ids = implode("','", $ids);
            if ($sendId) {
                $map = 'send_id';
            } else {
                $map = 'id';
            }
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                [
                    'message' => $message,
                    'send_status' => \Dotdigitalgroup\Email\Model\Campaign::FAILED,
                    'sent_at' => time()
                ],
                ["$map in ('$ids')"]
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
                'sent_at' => time()
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
            $ids = implode("','", $ids);
            $bind = [
                'send_status' => \Dotdigitalgroup\Email\Model\Campaign::PROCESSING,
                'send_id' => $sendId
            ];
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                $bind,
                ["id in ('$ids')"]
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }
}
