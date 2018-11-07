<?php

namespace Dotdigitalgroup\Email\Model;

class FailedAuth extends \Magento\Framework\Model\AbstractModel
{
    const NUMBER_MAX_FAILS_LIMIT = '5';

    public function _construct()
    {
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\FailedAuth::class);
    }

    /**
     * Check if the store is in the locked state.
     */
    public function isLocked()
    {
        if ($this->getFailuresNum() == \Dotdigitalgroup\Email\Model\FailedAuth::NUMBER_MAX_FAILS_LIMIT &&
            strtotime($this->getLastAttemptDate() . '+5 min') > time()) {
            return true;
        }

        return false;
    }
}
