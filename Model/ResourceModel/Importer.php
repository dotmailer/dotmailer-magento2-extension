<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Importer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $localeDate;

    /**
     * Importer constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->localeDate = $localeDate;
        parent::__construct($context);
    }

    /**
     * Initialize resource.
     */
    public function _construct() //@codingStandardsIgnoreLine
    {
        $this->_init('email_importer', 'id');
    }

    /**
     * Reset importer items.
     *
     * @param $ids
     *
     * @return int|string
     */
    public function massResend($ids)
    {
        try {
            $conn = $this->getConnection();
            $num = $conn->update(
                $this->getTable('email_importer'),
                ['import_status' => 0],
                ['id IN(?)' => $ids]
            );

            return $num;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Delete completed records older then 30 days from provided table.
     *
     * @param $tableName
     *
     * @return \Exception|int
     */
    public function cleanup($tableName)
    {
        try {
            $interval = \DateInterval::createFromDateString('30 day');
            $date = $this->localeDate->date()->sub($interval)->format('Y-m-d H:i:s');
            $conn = $this->getConnection();
            $num = $conn->delete(
                $tableName,
                ['created_at < ?' => $date]
            );

            return $num;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
