<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Automapdatafields extends AbstractDeveloper
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
        $website = $this->getRequest()->getParam('website', 0);
        $params = ['website' => $website];
        return $this->_urlBuilder->getUrl('dotdigitalgroup_email/run/automapdatafields', $params);
    }
}
