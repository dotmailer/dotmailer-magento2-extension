<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Contact extends \Dotdigitalgroup\Email\Block\Adminhtml\Config\Report\Report
{
    /**
     * @return string
     */
    public function getLink()
    {
        return $this->getUrl(
            'dotdigitalgroup_email/contact/index'
        );
    }
}
