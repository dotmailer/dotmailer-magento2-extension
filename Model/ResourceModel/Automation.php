<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Automation extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_automation', 'id');
    }

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->helper = $data;
        parent::__construct($context);
    }

    /**
     * update status for automation entries
     *
     * @param $contactIds
     * @param $status
     * @param $message
     * @param $updatedAt
     * @param $type
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateStatus($contactIds, $status, $message, $updatedAt, $type)
    {
        $conn = $this->getConnection();
        try {
            $bind = [
                'enrolment_status' => $status,
                'message' => $message,
                'updated_at' => $updatedAt,
            ];
            $where = ['id IN(?)' => $contactIds];
            $num = $conn->update(
                $conn->getTableName('email_automation'),
                $bind,
                $where
            );
            //number of updated records
            if ($num) {
                $this->helper->log(
                    'Automation type : ' . $type . ', updated : ' . $num
                );
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }
}
