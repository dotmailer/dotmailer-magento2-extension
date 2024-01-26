<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Backend;

class DailyIntervalCron extends \Magento\Framework\App\Config\Value
{
    private const EVERY_THIRTY_DAYS = '0 0 1 * *';
    private const EVERY_FOURTEEN_DAYS = '0 0 */14 * *';
    private const EVERY_SEVEN_DAYS = '0 0 * * 0';
    private const EVERY_DAY = '0 0 * * *';

    /**
     * After load.
     *
     * @return DailyIntervalCron
     */
    protected function _afterLoad()
    {
        switch ($this->getValue()) {
            case self::EVERY_DAY:
                $this->setValue(1);
                break;
            case self::EVERY_SEVEN_DAYS:
                $this->setValue(7);
                break;
            case self::EVERY_FOURTEEN_DAYS:
                $this->setValue(14);
                break;
            default:
                $this->setValue(30);
        }
        return parent::_afterLoad();
    }

    /**
     * Before save.
     *
     * @return DailyIntervalCron|void
     */
    public function beforeSave()
    {
        switch ($this->getValue()) {
            case 1:
                $this->setValue(self::EVERY_DAY);
                break;
            case 7:
                $this->setValue(self::EVERY_SEVEN_DAYS);
                break;
            case 14:
                $this->setValue(self::EVERY_FOURTEEN_DAYS);
                break;
            default:
                $this->setValue(self::EVERY_THIRTY_DAYS);
        }
    }
}
