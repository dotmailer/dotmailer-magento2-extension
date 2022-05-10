<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

use Dotdigitalgroup\Email\Block\Adminhtml\Config\AbstractButton;

class Automapdatafields extends AbstractButton
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
        $website = $this->getRequest()->getParam('website', 0);
        $params = ['website' => $website];
        return $this->_urlBuilder->getUrl('dotdigitalgroup_email/run/automapdatafields', $params);
    }
}
