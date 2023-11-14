<?php

namespace Dotdigitalgroup\Email\Model;

class FailedAuth extends \Magento\Framework\Model\AbstractModel
{
    public const NUMBER_MAX_FAILS_LIMIT = '5';

    /**
     * FailedAuth constructor.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\FailedAuth::class);
    }

    /**
     * Check if the store is in the locked state.
     *
     * @return bool
     */
    public function isLocked()
    {
        if ($this->getFailuresNum() == self::NUMBER_MAX_FAILS_LIMIT &&
            strtotime($this->getLastAttemptDate() . '+5 min') > time()) {
            return true;
        }

        return false;
    }
}
