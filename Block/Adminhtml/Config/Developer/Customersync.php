<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

use Dotdigitalgroup\Email\Block\Adminhtml\Config\AbstractButton;

class Customersync extends AbstractButton
{
    /**
     * Get disabled.
     *
     * @return bool
     */
    protected function getDisabled()
    {
        return false;
    }

    /**
     * Get button label.
     *
     * @return \Magento\Framework\Phrase|string
     */
    protected function getButtonLabel()
    {
        return  __('Run Now');
    }

    /**
     * Get button url.
     *
     * @return string
     */
    protected function getButtonUrl()
    {
        return $this->escapeUrl($this->_urlBuilder->getUrl('dotdigitalgroup_email/run/customersync'));
    }
}
