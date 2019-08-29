<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Resetwishlists extends AbstractDeveloper
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
        $query = [
            '_query' => [
                'from' => '',
                'to' => '',
                'tp' => ''
            ]
        ];
        return $this->_urlBuilder->getUrl('dotdigitalgroup_email/run/wishlistsreset', $query);
    }
}
