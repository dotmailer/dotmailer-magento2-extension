<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Contactsync extends AbstractDeveloper
{
    /**
     * @return bool
     */
    protected function getDisabled()
    {
        return false;
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    protected function getButtonLabel()
    {
        return  __('Run Now');
    }

    /**
     * @return string
     */
    protected function getButtonUrl()
    {
        return $this->escapeUrl($this->_urlBuilder->getUrl('dotdigitalgroup_email/run/contactsync'));
    }
}
