<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Importer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * @var \Dotdigitalgroup\Email\Model\DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * Importer constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
    ) {
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->localeDate = $localeDate;
        parent::__construct($context);
    }

    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init('email_importer', 'id');
    }

    /**
     * Reset importer items.
     *
     * @param array $ids
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
     * @param string $tableName
     *
     * @return \Exception|int
     */
    public function cleanup($tableName)
    {
        try {
            $interval = $this->dateIntervalFactory->create(['interval_spec' => 'P30D']);
            $date = $this->localeDate->date()->sub($interval)->format('Y-m-d H:i:s');
            $conn = $this->getConnection();
            $num = $conn->delete(
                $this->getTable($tableName),
                ['created_at < ?' => $date]
            );

            return $num;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Save item
     *
     * @param \Dotdigitalgroup\Email\Model\\Importer $item
     *
     * @return $this
     */
    public function saveItem($item)
    {
        return $this->save($item);
    }
}
