<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Review extends \Magento\Config\Block\System\Config\Form\Field
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
