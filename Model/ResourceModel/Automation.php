<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class Automation extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
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
        $this->_init(Schema::EMAIL_AUTOMATION_TABLE, 'id');
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
     * Update status for automation entries
     *
     * @param array $rowIds
     * @param string $status
     * @param string $message
     * @param string $updatedAt
     * @param string $type
     *
     * @return void
     */
    public function updateStatus($rowIds, $status, $message, $updatedAt, $type)
    {
        $bind = [
            'enrolment_status' => $status,
            'message' => $message,
            'updated_at' => $updatedAt,
        ];
        $where = ['id IN(?)' => $rowIds];
        $num = $this->getConnection()->update(
            $this->getTable(Schema::EMAIL_AUTOMATION_TABLE),
            $bind,
            $where
        );
        //number of updated records
        if ($num) {
            $this->helper->log(
                'Automation type : ' . $type . ', updated : ' . $num
            );
        }
    }

    /**
     * @param array $ids
     * @param string $date
     * @param bool $enrolmentStatus
     */
    public function update($ids, $date, $enrolmentStatus = false)
    {
        $bind = ['updated_at' => $date];
        if ($enrolmentStatus) {
            $bind['enrolment_status'] = $enrolmentStatus;
        }

        $where = ['id IN(?)' => $ids];
        $this->getConnection()->update(
            $this->getTable(Schema::EMAIL_AUTOMATION_TABLE),
            $bind,
            $where
        );
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Automation $automation
     * @param string $status
     * @param string $message
     *
     * @return void
     */
    public function setStatusAndSaveAutomation($automation, $status, $message = '')
    {
        $automation->setEnrolmentStatus($status);
        $automation->setMessage($message);
        $this->save($automation);
    }
}
