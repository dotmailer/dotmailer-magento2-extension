<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Review extends \Dotdigitalgroup\Email\Block\Adminhtml\Config\Report\Report
{
    /**
     * @return string
     */
    public function getLink()
    {
        return $this->getUrl(
            'dotdigitalgroup_email/review/index'
        );
    }
}
